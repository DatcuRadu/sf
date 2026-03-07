<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInventoryBatchJob;
use App\Models\InventoryFile;
use App\Services\Epicor\Inventory\InventoryProcessor;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessInventory extends Command
{
    protected $signature = 'inventory:process';
    protected $description = 'Process Epicor inventory feeds safely (FULL + DELTA)';

    private const FULL_PREFIX = 'VM_Full_Inventory_File_';
    private const DELTA_PREFIX = 'VM_Inv_Delta_';

    private const STATUS_PROCESSING = 'processing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const STATUS_SKIPPED = 'skipped';

    private const TYPE_FULL = 'full';
    private const TYPE_DELTA = 'delta';

    private const BATCH_SIZE = 300;

    public function handle(InventoryProcessor $processor): int
    {
        $this->cleanupStaleProcesses();

        $activeProcess = $this->getActiveProcess();

        if ($activeProcess) {
            $this->info("Inventory still processing. Active inventory_file_id={$activeProcess->id}");
            return self::SUCCESS;
        }

        $candidate = $this->resolveNextCandidate($processor);

        if (!$candidate) {
            $this->info('Nothing to process.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Selected %s file: %s (%s)',
            strtoupper($candidate['type']),
            $candidate['path'],
            $candidate['received_at']->toDateTimeString()
        ));

        return $this->dispatchFile(
            processor: $processor,
            originalPath: $candidate['path'],
            type: $candidate['type'],
            receivedAt: $candidate['received_at']
        );
    }

    private function cleanupStaleProcesses(): void
    {
        $staleItems = InventoryFile::query()
            ->where('status', self::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->where('started_at', '<', now()->subHours(3))
            ->get();

        foreach ($staleItems as $item) {
            $batchFinished = false;

            if ($item->batch_id) {
                $batch = Bus::findBatch($item->batch_id);

                if ($batch) {
                    $batchFinished = $batch->finished();

                    if ($batchFinished) {
                        $finalStatus = $batch->failedJobs > 0
                            ? self::STATUS_FAILED
                            : self::STATUS_COMPLETED;

                        $item->update([
                            'status' => $finalStatus,
                            'finished_at' => now(),
                            'error_message' => $finalStatus === self::STATUS_FAILED
                                ? 'Recovered stale process: batch already finished with failed jobs.'
                                : null,
                        ]);

                        continue;
                    }
                }
            }

            $item->update([
                'status' => self::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => 'Process marked as failed automatically because it was stale for more than 3 hours.',
            ]);
        }
    }

    private function getActiveProcess(): ?InventoryFile
    {
        $processing = InventoryFile::query()
            ->where('status', self::STATUS_PROCESSING)
            ->orderByDesc('started_at')
            ->first();

        if (!$processing) {
            return null;
        }

        if (!$processing->batch_id) {
            return $processing;
        }

        $batch = Bus::findBatch($processing->batch_id);

        if (!$batch) {
            $processing->update([
                'status' => self::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => 'Batch not found for processing inventory item.',
            ]);

            return null;
        }

        if (!$batch->finished()) {
            return $processing;
        }

        $processing->update([
            'status' => $batch->failedJobs > 0 ? self::STATUS_FAILED : self::STATUS_COMPLETED,
            'finished_at' => now(),
            'error_message' => $batch->failedJobs > 0
                ? "Batch finished with {$batch->failedJobs} failed job(s)."
                : null,
        ]);

        return null;
    }

    private function resolveNextCandidate(InventoryProcessor $processor): ?array
    {
        $fullPath = $processor->getLatestFile(self::FULL_PREFIX);
        $deltaPath = $processor->getLatestFile(self::DELTA_PREFIX);

        $fullReceivedAt = $fullPath ? $processor->getLastModifiedAt($fullPath) : null;
        $deltaReceivedAt = $deltaPath ? $processor->getLastModifiedAt($deltaPath) : null;

        $lastCompleted = InventoryFile::query()
            ->where('status', self::STATUS_COMPLETED)
            ->orderByDesc('received_at')
            ->first();

        $lastCompletedFull = InventoryFile::query()
            ->where('type', self::TYPE_FULL)
            ->where('status', self::STATUS_COMPLETED)
            ->orderByDesc('received_at')
            ->first();

        if ($fullPath && $fullReceivedAt) {
            if ($this->isAlreadyKnown($fullPath, $fullReceivedAt, self::TYPE_FULL)) {
                $fullPath = null;
                $fullReceivedAt = null;
            }
        }

        if ($deltaPath && $deltaReceivedAt) {
            if ($this->isAlreadyKnown($deltaPath, $deltaReceivedAt, self::TYPE_DELTA)) {
                $deltaPath = null;
                $deltaReceivedAt = null;
            }
        }

        if ($fullPath && $fullReceivedAt) {
            if (!$lastCompleted || $fullReceivedAt->gt($lastCompleted->received_at)) {
                return [
                    'type' => self::TYPE_FULL,
                    'path' => $fullPath,
                    'received_at' => $fullReceivedAt,
                ];
            }
        }

        if ($deltaPath && $deltaReceivedAt) {
            if (!$lastCompletedFull) {
                $this->warn('Delta found, but no completed FULL exists yet. Delta ignored for now.');
                return null;
            }

            if ($deltaReceivedAt->lte($lastCompletedFull->received_at)) {
                $this->warn('Delta is older than or equal to the last completed FULL. Ignored.');
                return null;
            }

            if (!$lastCompleted || $deltaReceivedAt->gt($lastCompleted->received_at)) {
                return [
                    'type' => self::TYPE_DELTA,
                    'path' => $deltaPath,
                    'received_at' => $deltaReceivedAt,
                ];
            }
        }

        return null;
    }

    private function isAlreadyKnown(string $fileName, $receivedAt, string $type): bool
    {
        return InventoryFile::query()
            ->where('file_name', $fileName)
            ->where('type', $type)
            ->where('received_at', $receivedAt)
            ->exists();
    }

    private function dispatchFile(
        InventoryProcessor $processor,
        string $originalPath,
        string $type,
                           $receivedAt
    ): int {
        DB::beginTransaction();

        try {
            $bufferPath = $processor->moveToBufferAndRename($originalPath);

            $totalRows = 0;
            foreach ($processor->streamCsv($bufferPath) as $row) {
                $totalRows++;
            }

            if ($totalRows === 0) {
                InventoryFile::create([
                    'file_name' => $bufferPath,
                    'type' => $type,
                    'status' => self::STATUS_SKIPPED,
                    'started_at' => now(),
                    'finished_at' => now(),
                    'total_rows' => 0,
                    'received_at' => $receivedAt,
                    'error_message' => 'Empty inventory file.',
                ]);

                DB::commit();

                $this->warn("{$type} file is empty. Skipped.");
                return self::SUCCESS;
            }

            $inventoryFile = InventoryFile::create([
                'file_name' => $bufferPath,
                'type' => $type,
                'status' => self::STATUS_PROCESSING,
                'started_at' => now(),
                'total_rows' => $totalRows,
                'received_at' => $receivedAt,
            ]);

            $jobs = [];

            for ($offset = 0; $offset < $totalRows; $offset += self::BATCH_SIZE) {
                $jobs[] = new ProcessInventoryBatchJob(
                    $bufferPath,
                    $offset,
                    self::BATCH_SIZE,
                    $inventoryFile->id
                );
            }

            $batch = Bus::batch($jobs)
                ->name(sprintf('inventory:%s:%d', $type, $inventoryFile->id))
                ->then(function (Batch $batch) use ($processor, $bufferPath, $inventoryFile): void {
                    $archivePath = $processor->archiveFile($bufferPath);

                    $inventoryFile->refresh();
                    $inventoryFile->update([
                        'status' => self::STATUS_COMPLETED,
                        'finished_at' => now(),
                        'archive_path' => $archivePath,
                        'error_message' => null,
                    ]);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($inventoryFile): void {
                    $inventoryFile->refresh();
                    $inventoryFile->update([
                        'status' => self::STATUS_FAILED,
                        'finished_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]);
                })
                ->finally(function (Batch $batch) use ($inventoryFile): void {
                    $inventoryFile->refresh();

                    if ($inventoryFile->status === self::STATUS_PROCESSING) {
                        $inventoryFile->update([
                            'status' => $batch->failedJobs > 0
                                ? self::STATUS_FAILED
                                : self::STATUS_COMPLETED,
                            'finished_at' => now(),
                            'error_message' => $batch->failedJobs > 0
                                ? "Batch finalized with {$batch->failedJobs} failed job(s)."
                                : null,
                        ]);
                    }
                })
                ->dispatch();

            $inventoryFile->update([
                'batch_id' => $batch->id,
            ]);

            DB::commit();

            $this->info("Dispatched {$type} inventory. inventory_file_id={$inventoryFile->id}, batch_id={$batch->id}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();

            report($e);
            $this->error("Inventory dispatch failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}