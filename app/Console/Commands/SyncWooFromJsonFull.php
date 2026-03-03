<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWooFromJsonFull extends Command
{
    protected $signature = 'epicor:sync-woo-full';
    protected $description = 'Full sync from Woo JSON with reporting';

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->info("Loading JSON...");

        $skuMap = json_decode(
            file_get_contents(storage_path('app/EPICOR/Info/sku_to_full_data.json')),
            true
        );

        if (!$skuMap) {
            $this->error("Invalid JSON.");
            return;
        }

        $notFoundInDb = [];
        $gtinMismatch = [];
        $updated = 0;

        foreach ($skuMap as $sku => $jsonData) {

            $cleanSku = trim($sku, '"');

            $product = DB::table('products')
                ->where('sku', $cleanSku)
                ->first();

            // ❌ SKU din JSON nu există în DB
            if (!$product) {
                $notFoundInDb[] = $cleanSku;
                continue;
            }

            // 🔎 Verificare GTIN dacă ambele există
            if (!empty($product->gtin) && !empty($jsonData['gtin'])) {

                if (trim($product->gtin) !== trim($jsonData['gtin'])) {
                    $gtinMismatch[] = $cleanSku;
                    continue;
                }
            }

            // ✅ Update direct (rapid)
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'woo_product_id' => $jsonData['product_id'],
                    'woo_parent_id'  => $jsonData['parent_id']
                ]);

            $updated++;
        }

        // 🔥 Salvăm rapoarte
        file_put_contents(
            storage_path('app/EPICOR/Info/not_found_in_db.json'),
            json_encode($notFoundInDb, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            storage_path('app/EPICOR/Info/gtin_mismatch.json'),
            json_encode($gtinMismatch, JSON_PRETTY_PRINT)
        );

        $this->info("Updated products: {$updated}");
        $this->info("Not found in DB: " . count($notFoundInDb));
        $this->info("GTIN mismatch: " . count($gtinMismatch));
    }
}