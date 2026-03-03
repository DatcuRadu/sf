<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WooCommerceProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WooSyncProducts extends Command
{
    protected $signature = 'woo:sync-products {--limit=200}';
    protected $description = 'Sync WooCommerce products';

    public function __construct(
        protected WooCommerceProductSyncService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $lock = Cache::lock('woo-sync-products', 300);

        if (!$lock->get()) {
            $this->info('Already running.');
            return Command::SUCCESS;
        }

        try {

            $limit = (int) $this->option('limit');

            Product::where('to_sync', 1)
                ->orderBy('id')
                ->limit($limit)
                ->chunkById(100, function ($products) {

                    foreach ($products as $product) {

                        try {

                            $result = $this->service->sync($product);

                            $this->info("SKU {$product->sku} → {$result['status']}");

                            if (in_array($result['status'], ['updated', 'no_changes'])) {
                                $product->update(['to_sync' => 0]);
                            }

                        } catch (\Throwable $e) {

                            logger()->error('Woo sync failed', [
                                'product_id' => $product->id,
                                'sku'        => $product->sku,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }
                });

            $this->info('Done.');

            return Command::SUCCESS;

        } finally {
            optional($lock)->release();
        }
    }
}