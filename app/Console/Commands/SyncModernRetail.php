<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use League\Csv\Reader;

class SyncModernRetail extends Command
{
    protected $signature = 'sync:modern-retail {--offset=0}';

    protected $description = 'Sync original_id and deleted flag from ModernRetail CSV';

    public function handle()
    {
        // dezactivează Telescope
        config(['telescope.enabled' => false]);

        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->info("Loading CSV...");

        $path = storage_path('app/EPICOR/Info/modernretail.csv');

        if (!file_exists($path)) {
            $this->error("CSV file not found: $path");
            return;
        }

        $offset = (int)$this->option('offset');

        $csv = Reader::createFromPath($path, 'r');
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(null);

        $records = $csv->getRecords();

        $current = 0;
        $updated = 0;

        foreach ($records as $row) {

            $current++;

            // skip până la offset
            if ($current <= $offset) {
                continue;
            }

            // CSV format:
            // [0] empty
            // [1] original_id OR 108917warning_amberDeleted from Platform
            // [2] title
            // [3] sku

            if (!isset($row[3])) {
                continue;
            }

            $rawOriginal = trim($row[1] ?? '');
            $sku         = trim($row[3] ?? '');

            if (!$sku) {
                continue;
            }

            $deleted = false;
            $originalId = null;

            if (str_contains($rawOriginal, 'Deleted from Platform')) {

                $deleted = true;

                if (preg_match('/^\d+/', $rawOriginal, $matches)) {
                    $originalId = (int)$matches[0];
                }

            } else {

                $originalId = (int)$rawOriginal;

            }

            $product = Product::where('sku', $sku)->first();

            if (!$product) {
                continue;
            }

            $product->update([
                'original_id' => $originalId,
                'deleted' => $deleted
            ]);

            $updated++;

            if ($current % 1000 === 0) {
                $this->info("Processed: $current | Updated: $updated");
            }
        }

        $this->info("Finished");
        $this->info("Rows processed: $current");
        $this->info("Products updated: $updated");
    }
}