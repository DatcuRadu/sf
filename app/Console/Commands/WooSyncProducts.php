<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Jobs\WooSincronization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WooSyncProducts extends Command
{
    protected $signature = 'woo:sync-products {--limit=200}';
    protected $description = 'Dispatch WooCommerce sync jobs in parallel';

    public function handle(): int
    {
        $lock = Cache::lock('woo-sync-products', 600);

        if (!$lock->get()) {
            $this->info('Woo sync is already running.');
            return Command::SUCCESS;
        }

        try {

            $limit = (int) $this->option('limit');

            $this->info("Dispatching up to {$limit} products...");

            $query = Product::where('to_sync', 1)
                ->orderBy('id')
                ->where('deleted', 0)
                ->limit($limit);

            $total = $query->count();

            if ($total === 0) {
                $this->info('No products to sync.');
                return Command::SUCCESS;
            }

            $this->info("Found {$total} products to queue.");

            $dispatched = 0;

            $query->chunkById(200, function ($products) use (&$dispatched) {

                foreach ($products as $product) {
                    $product->update(['to_sync' => 3]);

                    WooSincronization::dispatch($product);

                    $dispatched++;

                    $this->line("Queued SKU {$product->sku}");
                }
            });

            $this->info("All jobs dispatched. Total queued: {$dispatched}");

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $this->error('Dispatch failed: ' . $e->getMessage());

            logger()->error('Woo sync dispatch error', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;

        } finally {

            optional($lock)->release();
        }
    }
}