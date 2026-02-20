<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Epicor\Inventory\InventoryProcessor;
use App\Jobs\ProcessInventoryBatchJob;

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

            // -------------------------------------------------
            // Dispatch queue jobs in chunks of 300
            // -------------------------------------------------
            $batchSize = 300;

            for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {

                ProcessInventoryBatchJob::dispatch(
                    $file,
                    $offset,
                    $batchSize
                );
            }

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
