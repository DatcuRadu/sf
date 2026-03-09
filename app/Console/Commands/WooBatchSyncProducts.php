<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Jobs\WooBatchSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WooBatchSyncProducts extends Command
{
    protected $signature = 'woo:sync-products-batch 
                            {--limit=1000}
                            {--chunk=100}';

    protected $description = 'Dispatch WooCommerce batch sync jobs';

    public function handle(): int
    {
        $lock = Cache::lock('woo-batch-sync', 600);

        if (! $lock->get()) {
            $this->info('Woo batch sync already running.');
            return self::SUCCESS;
        }

        try {

            $limit = (int) $this->option('limit');
            $chunk = (int) $this->option('chunk');

            $ids = Product::query()
                ->where('to_sync', 1)
                ->where('deleted', 0)
                ->orderBy('updated_at', 'asc')
                ->limit($limit)
                ->pluck('id');

            if ($ids->isEmpty()) {

                $this->info('No products to sync.');

                return self::SUCCESS;
            }

            $jobs = [];

            foreach ($ids->chunk($chunk) as $chunkIds) {

                $jobs[] = new WooBatchSyncJob(
                    $chunkIds->values()->all()
                );

            }

            $batch = Bus::batch($jobs)
                ->name('Woo Products Sync')
                ->dispatch();

            $this->info("Batch dispatched: {$batch->id}");
            $this->info("Jobs created: " . count($jobs));

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error($e->getMessage());

            return self::FAILURE;

        } finally {

            optional($lock)->release();

        }
    }
}