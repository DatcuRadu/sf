<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckWooDifferences extends Command
{
    protected $signature = 'epicor:check-woo-diff';
    protected $description = 'Check differences between Woo JSON and DB';

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->info("Loading JSON...");

        $path = storage_path('app/EPICOR/Info/last_data.json');

        if (!file_exists($path)) {
            $this->error("JSON file not found.");
            return;
        }

        $skuMap = json_decode(file_get_contents($path), true);

        if (!$skuMap) {
            $this->error("Invalid JSON.");
            return;
        }

        $priceDiff = [];
        $stockDiff = [];
        $idDiff = [];
        $notFound = [];

        foreach ($skuMap as $sku => $jsonData) {

            $cleanSku = trim($sku, '"');

            $product = DB::table('products')
                ->select(
                    'id',
                    'sku',
                    'regular_price',
                    'sale_price',
                    'qty',
                    'woo_product_id',
                    'woo_parent_id'
                )
                ->where('sku', $cleanSku)
                ->first();

            if (!$product) {
                $notFound[] = $cleanSku;
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | PRICE CHECK
            |--------------------------------------------------------------------------
            */

            $dbRegular = (float) $product->regular_price;
            $jsonRegular = (float) ($jsonData['regular_price'] ?? 0);

            $dbSale = (float) $product->sale_price;
            $jsonSale = (float) ($jsonData['sale_price'] ?? 0);

            if ($dbRegular !== $jsonRegular || $dbSale !== $jsonSale) {

                $priceDiff[$cleanSku] = [
                    'db_regular_price' => $dbRegular,
                    'json_regular_price' => $jsonRegular,
                    'db_sale_price' => $dbSale,
                    'json_sale_price' => $jsonSale
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | STOCK CHECK
            |--------------------------------------------------------------------------
            */

            $dbQty = (int) $product->qty;
            $jsonQty = (int) ($jsonData['stock_quantity'] ?? 0);

            if ($dbQty !== $jsonQty) {

                $stockDiff[$cleanSku] = [
                    'db_qty' => $dbQty,
                    'json_qty' => $jsonQty
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | PRODUCT / VARIATION ID CHECK
            |--------------------------------------------------------------------------
            */

            $dbProductId = (int) $product->woo_product_id;
            $jsonProductId = (int) ($jsonData['product_id'] ?? 0);

            $dbParentId = (int) $product->woo_parent_id;
            $jsonParentId = (int) ($jsonData['parent_id'] ?? 0);

            if ($dbProductId !== $jsonProductId || $dbParentId !== $jsonParentId) {

                $idDiff[$cleanSku] = [
                    'db_product_id' => $dbProductId,
                    'json_product_id' => $jsonProductId,
                    'db_parent_id' => $dbParentId,
                    'json_parent_id' => $jsonParentId
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE REPORTS
        |--------------------------------------------------------------------------
        */

        $base = storage_path('app/EPICOR/Info/');

        file_put_contents(
            $base . 'price_diff.json',
            json_encode($priceDiff, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $base . 'stock_diff.json',
            json_encode($stockDiff, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $base . 'id_diff.json',
            json_encode($idDiff, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $base . 'not_found.json',
            json_encode($notFound, JSON_PRETTY_PRINT)
        );

        $this->info("Price differences: " . count($priceDiff));
        $this->info("Stock differences: " . count($stockDiff));
        $this->info("ID differences: " . count($idDiff));
        $this->info("Not found: " . count($notFound));

        $this->info("Reports saved to storage/app/EPICOR/Info/");
    }
}