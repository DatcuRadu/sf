<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\WooCommerceProductSyncService;

class SyncLocalProductsWithWoo extends Command
{
    protected $signature = 'woo:match-local';
    protected $description = 'Match local products with WooCommerce using existing service';

    public function handle(WooCommerceProductSyncService $wooService)
    {
        $this->info('Starting matching process...');

        Product::whereNotNull('sku')
            ->chunk(50, function ($products) use ($wooService) {

                foreach ($products as $product) {

                    $wooProducts = $wooService->findBySku($product->sku);

                    if (!empty($wooProducts)) {

                        $wooId = $wooProducts[0]['id'];

                        $product->update([
                            'original_id' => $wooId,
                            'to_sync' => 0,
                        ]);

                        $this->info("Matched SKU {$product->sku} → Woo ID {$wooId}");

                    } else {

                        $product->update([
                            'to_sync' => 1,
                        ]);

                        $this->warn("SKU {$product->sku} not found in Woo");
                    }
                }
            });

        $this->info('Matching completed.');
    }
}
