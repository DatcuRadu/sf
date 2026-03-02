<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WooCommerceProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WooSyncProducts extends Command
{
    protected $signature = 'woo:sync-products {--limit=200}';
    protected $description = 'Sync WooCommerce products (simple version)';

    public function __construct(
        protected WooCommerceProductSyncService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // 🔒 Lock global — dacă rulează deja, ieșim
        $lock = Cache::lock('woo-sync-aproducts', 300);

        if (! $lock->get()) {
            $this->info('Already running. Skipping.');
            return Command::SUCCESS;
        }

        try {

            $limit = (int) $this->option('limit');

            $products = Product::where('to_sync', 1)
                ->whereNotNull('original_id')
                ->orderBy('id')
                ->limit($limit)
                ->get();

            if ($products->isEmpty()) {
                $this->info('No products to sync.');
                return Command::SUCCESS;
            }

            $ok = 0;
            $failed = 0;

            foreach ($products as $product) {

                try {


                    if($product->original_id){
                        $payload = [
                            'productId'          => $product->original_id,
                            'regularPrice' => (float) $product->regular_price,
                            'salePrice'    => (float) $product->sale_price,
                            'qty'          => (int) $product->qty,
                            'saleStart'    => $product->sales_start?->format('Y-m-d'),
                            'saleEnd'      => $product->sales_end?->format('Y-m-d'),
                        ];

                        // elimină null


                        $result = $this->service->syncByProductId(...$payload);
                    } else {


                    $payload = [
                        'sku'          => $product->sku,
                        'gtin'         => $product->gitn ?: null,
                        'regularPrice' => (float) $product->regular_price,
                        'salePrice'    => (float) $product->sale_price,
                        'qty'          => (int) $product->qty,
                        'saleStart'    => $product->sales_start?->format('Y-m-d'),
                        'saleEnd'      => $product->sales_end?->format('Y-m-d'),
                    ];


                    $result = $this->service->sfync(...$payload);
                    }

                    $this->info('Result:');
                    $this->line(print_r($result, true));

                    if (($result['status'] ?? null) !== 'not_found') {
                        $product->update(['to_sync' => 0]);
                    }

                    $ok++;

                } catch (\Throwable $e) {

                    $failed++;

                    logger()->error('Woo sync failed', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Done. OK={$ok}, Failed={$failed}");

            return Command::SUCCESS;

        } finally {
            optional($lock)->release();
        }
    }
}