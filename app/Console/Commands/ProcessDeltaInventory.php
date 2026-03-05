<?php

namespace App\Console\Commands;

use App\Models\InventoryFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Epicor\Inventory\InventoryProcessor;
use App\Jobs\ProcessInventoryBatchJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use Throwable;

class ProcessDeltaInventory extends Command
{
    protected $signature = 'inventory:delta';
    protected $description = 'Process Epicor Delta Inventory file using queue batches';

    public function handle(InventoryProcessor $processor)
    {


        try {

            $file = $processor->getLatestFile('VM_Inv_Delta_');

            if (!$file) {
                $this->info('No Delta inventory file found.');
                return Command::SUCCESS;
            }

            $file = $processor->moveToBufferAndRename($file);
            $received_at = $processor->getLastModifiedAt($file);

            $this->info("Processing Delta file: {$file}");

            // -------------------------------------------------
            // Count total rows safely
            // -------------------------------------------------
            $totalRows = 0;
            foreach ($processor->streamCsv($file) as $row) {
                $totalRows++;
            }

            if ($totalRows === 0) {
                $this->info('Delta file is empty.');
                return Command::SUCCESS;
            }

            $this->info("Total Delta rows detected: {$totalRows}");

            $batchSize = 300;
            $jobs = [];
            $inventoryFile = InventoryFile::create([
                'file_name' => $file,
                'type' => 'delta', // sau full
                'status' => 'processing',
                'started_at' => now(),
                'total_rows' => $totalRows,
                'received_at' => $received_at
            ]);

            for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {

                $jobs[] = new ProcessInventoryBatchJob(
                    $file,
                    $offset,
                    $batchSize,
                    $inventoryFile->id
                );
            }


            Bus::batch($jobs)
                ->then(function (Batch $batch) use ($processor, $file, $inventoryFile) {
                   $archive_path= $processor->archiveFile($file);
                    $inventoryFile->update([
                        'status' => 'completed',
                        'finished_at' => now(),
                        'archive_path' => $archive_path,
                    ]);
                    \Log::info("Inventory file archived successfully: {$file}");
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    \Log::error("Inventory batch failed: " . $e->getMessage());
                })
                ->dispatch();

            $this->info('All Delta batches dispatched to queue.');

            // -------------------------------------------------
            // Archive file after dispatch
            // -------------------------------------------------
            // $processor->archiveFile($file);

            $this->info('Delta file archived successfully.');

        } finally {

        }

        return Command::SUCCESS;
    }
}
