<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\WooCommerceProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WooSincronization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Product $product;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(WooCommerceProductSyncService $service): void
    {
        try {

            $result = $service->sync($this->product);

            if($result['status']==='not_found'){
                $this->product->update(['to_sync' => 2]);
            }

            // ✅ SUCCESS
            if (in_array($result['status'], ['updated', 'no_changes', 'created'])) {
                $this->product->update(['to_sync' => 0]);
            }

            Log::info("WooSync success", [
                'sku' => $this->product->sku
            ]);

        } catch (\Throwable $e) {

            Log::warning("WooSync attempt failed", [
                'sku' => $this->product->sku,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            throw $e; // permite retry
        }
    }

    /**
     * Se execută după 3 încercări eșuate
     */
    public function failed(\Throwable $exception): void
    {
        $this->product->update(['to_sync' => 0]);

        Log::critical("WooSync FAILED after 3 attempts", [
            'sku' => $this->product->sku,
            'error' => $exception->getMessage()
        ]);
    }
}