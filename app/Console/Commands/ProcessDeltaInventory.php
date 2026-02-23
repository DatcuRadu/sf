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
        // -------------------------------------------------
        // Global lock prevents Full & Delta running together
        // -------------------------------------------------
        $lock = Cache::lock('inventory_global_lock', 3600);

        if (! $lock->get()) {
            $this->warn('Another inventory job is currently running.');
            return Command::SUCCESS;
        }

        try {

            // -------------------------------------------------
            // Get latest DELTA file
            // -------------------------------------------------
            $file = $processor->getLatestFile('VM_Inv_Delta_');

            if (!$file) {
                $this->info('No Delta inventory file found.');
                return Command::SUCCESS;
            }

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
                'total_rows'=>$totalRows
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
                    $processor->archiveFile($file);
                    $inventoryFile->update([
                        'status' => 'completed',
                        'finished_at' => now(),
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
            $lock->release();
        }

        return Command::SUCCESS;
    }
}
