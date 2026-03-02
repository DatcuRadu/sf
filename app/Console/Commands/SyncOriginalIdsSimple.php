<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class SyncOriginalIdsSimple extends Command
{
    protected $signature = 'epicor:sync-original-simple';
    protected $description = 'One time sync original_id from SKU and GTIN JSON';

    public function handle()
    {

        ini_set('memory_limit', '99024M');
        set_time_limit(0);
        $this->info('Loading JSON files...');

        $basePath = storage_path('app/EPICOR/Info');

        $skuMap  = json_decode(File::get($basePath . '/sku_to_id.json'), true);
        $gtinMap = json_decode(File::get($basePath . '/gtin_to_id.json'), true);



        if (!$skuMap || !$gtinMap) {
            $this->error('Invalid JSON files.');
            return;
        }

        $updated = 0;

        //$products = Product::all();

        foreach (Product::cursor() as $product) {

            $wooId = null;

            // 1️⃣ Verificăm SKU
            if (!empty($product->sku) && isset($skuMap[$product->sku])) {
                $wooId = $skuMap[$product->sku];
            }

            // 2️⃣ Dacă nu există, verificăm GTIN
            elseif (!empty($product->gtin) && isset($gtinMap[$product->gtin])) {
                $wooId = $gtinMap[$product->gtin];
            }

            if ($wooId) {
                $product->original_id = $wooId;
                $product->save();
                $updated++;
            }
        }

        $this->info("Finished. Updated {$updated} products.");
    }
}