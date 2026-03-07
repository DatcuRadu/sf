<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Epicor\Inventory\InventoryProcessor;
use App\Jobs\ProcessInventoryBatchJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use Throwable;
use App\Models\InventoryFile;


class ProcessFullInventory extends Command
{
    protected $signature = 'inventory:full';
    protected $description = 'Process Epicor Full Inventory file using queue batches';

    public function handle(InventoryProcessor $processor)
    {


        try {

            // -------------------------------------------------
            // Get latest FULL inventory file
            // -------------------------------------------------
            $file = $processor->getLatestFile('VM_Full_Inventory_File_');

            if (!$file) {
                $this->info('No Full inventory file found.');
                return Command::SUCCESS;
            }

            $file = $processor->moveToBufferAndRename($file);
            $received_at = $processor->getLastModifiedAt($file);

            $this->info("Processing file: {$file}");

            // -------------------------------------------------
            // Count total rows without loading entire file
            // -------------------------------------------------
            $totalRows = 0;
            foreach ($processor->streamCsv($file) as $row) {
                $totalRows++;
            }

            if ($totalRows === 0) {
                $this->info('File is empty.');
                return Command::SUCCESS;
            }

            $this->info("Total rows detected: {$totalRows}");

            // -------------------------------------------------
            // Dispatch queue jobs in chunks of 300
            // -------------------------------------------------
            $batchSize = 300;
            $jobs = [];
            $inventoryFile = InventoryFile::create([
                'file_name' => $file,
                'type' => 'full', // sau full
                'status' => 'processing',
                'started_at' => now(),
                'total_rows'=>$totalRows,
                'received_at' => $received_at,
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

                })
                ->catch(function (Batch $batch, Throwable $e) use ($inventoryFile) {

                    $inventoryFile->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'finished_at' => now(),
                    ]);

                })
                ->dispatch();



            $this->info('All batches dispatched to queue.');

            // -------------------------------------------------
            // Archive file after dispatching jobs
            // -------------------------------------------------
           // $processor->archiveFile($file);

            $this->info('File archived successfully.');

        } finally {

        }

        return Command::SUCCESS;
    }
}
