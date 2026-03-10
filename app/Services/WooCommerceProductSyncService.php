<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WooCommerceSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooCommerceProductSyncService
{
    protected string $baseUrl;
    protected string $key;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('woocommerce.url'), '/');
        $this->key = config('woocommerce.key');
        $this->secret = config('woocommerce.secret');
    }

    protected function client()
    {
        return Http::withBasicAuth($this->key, $this->secret)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Entry point
     */
    public function sync(Product $product): array
    {
        $wooId = $product->woo_product_id;

        if (!$wooId) {
            return $this->searchAndAttachWooId($product);
        }

        return $this->syncByWooId($product, $wooId);
    }

    /**
     * STRICT SEARCH (SKU + GTIN AND logic)
     */
    protected function searchAndAttachWooId(Product $product): array
    {
        $matches = $this->searchWooProductStrict($product);

        if (empty($matches)) {
            if (empty($matches)) {
                return $this->createWooProduct($product);
            }
            return ['status' => 'not_found'];

        }

        if (count($matches) > 1) {
            Log::warning('Multiple strict Woo matches found', [
                'sku' => $product->sku,
                'gtin' => $product->gitn,
                'count' => count($matches),
            ]);
        }

        $woo = $matches[0];

        $product->update([
            'woo_product_id' => $woo['id'],
            'woo_parent_id' => $woo['parent_id'] ?? null,
        ]);

        return $this->syncByWooId($product, $woo['id']);
    }

    public function searchWooProductStrict(Product $product): array
    {
        $matches = [];

        try {
            // First try: Search by SKU directly if WooCommerce supports it
            $response = $this->client()->get(
                $this->baseUrl . '/wp-json/wc/v3/products',
                [
                    'sku' => $product->sku, // Use SKU parameter if available
                    'per_page' => 100,
                ]
            );

            $items = $response->json() ?? [];

            // If no results with SKU parameter, try search parameter
            if (empty($items)) {
                $response = $this->client()->get(
                    $this->baseUrl . '/wp-json/wc/v3/products',
                    [
                        'search' => $product->sku,
                        'per_page' => 100,
                    ]
                );

                $items = $response->json() ?? [];
            }

            // Filter results
            foreach ($items as $item) {

                $skuMatch = isset($item['sku']) &&
                    strtolower(trim($item['sku'])) === strtolower(trim($product->sku));

                if (!$skuMatch) {
                    continue;
                }


                $matches[] = $item;
            }

        } catch (\Exception $e) {
            // Log error if needed
            // logger()->error('WooCommerce search failed: ' . $e->getMessage());
            return [];
        }

        return $matches;
    }

    /**
     * Sync by Woo ID (handles simple + variation)
     */
    protected function syncByWooId(Product $product, int $wooId): array
    {
        $endpoint = $this->buildEndpoint($product, $wooId);

        $getResponse = $this->client()->get($this->baseUrl . $endpoint);

        if ($getResponse->failed()) {
            return ['status' => 'not_found_by_id'];
        }

        $woo = $getResponse->json();

        $updateData = $this->buildUpdateData($product, $woo);

        if (empty($updateData)) {
            return ['status' => 'no_changes'];
        }

        $putResponse = $this->client()->put(
            $this->baseUrl . $endpoint,
            $updateData
        );

        $status = $putResponse->successful() ? 'updated' : 'failed';

        WooCommerceSyncLog::create([
            'sku' => $product->sku,
            'woocommerce_product_id' => $wooId,
            'status' => $status,
            'old_data' => [
                'regular_price' => $woo['regular_price'] ?? null,
                'sale_price' => $woo['sale_price'] ?? null,
                'stock_quantity' => $woo['stock_quantity'] ?? null,
            ],
            'new_data' => $updateData,
            'response_payload' => $putResponse->json(),
        ]);

        return ['status' => $status];
    }

    protected function buildEndpoint(Product $product, int $wooId): string
    {
        // variation
        if ($product->woo_parent_id) {
            return '/wp-json/wc/v3/products/' .
                $product->woo_parent_id .
                '/variations/' . $wooId;
        }

        // simple
        return '/wp-json/wc/v3/products/' . $wooId;
    }

    protected function buildUpdateData(Product $product, array $woo): array
    {
        $updateData = [];

        if ((float)($woo['regular_price'] ?? 0) !== (float)$product->regular_price) {
            $updateData['regular_price'] = (string)$product->regular_price;
        }

        if ((float)($woo['sale_price'] ?? 0) !== (float)$product->sale_price) {

            $salePrice = (float) $product->sale_price;
            $now = Carbon::now();

            $start = $product->sales_start ? Carbon::parse($product->sales_start) : null;
            $end   = $product->sales_end ? Carbon::parse($product->sales_end) : null;

            if ($salePrice > 0 && (!$end || $end->greaterThan($now))) {

                $updateData['sale_price'] = (string) $salePrice;

                if ($start) {
                    $updateData['date_on_sale_from'] = $start->toIso8601String();
                }

                if ($end) {
                    $updateData['date_on_sale_to'] = $end->toIso8601String();
                }

            }
        }

        if ((int)($woo['stock_quantity'] ?? 0) !== (int)$product->qty) {
            $updateData['stock_quantity'] = (int)$product->qty;
            $updateData['manage_stock'] = true;
        }

        return $updateData;
    }


    protected function createWooProduct(Product $product): array
    {
        try {

            $payload = [
                'name' => $product->name ?? $product->sku,
                'description' => $product->description ?? '',
                'sku' => $product->sku,
                'status' => 'draft',
                'type' => 'simple',
                'regular_price' => (string)$product->regular_price,

                'manage_stock' => true,
                'stock_quantity' => (int)$product->qty,
                'meta_data' => [
                    [
                        'key' => '_wpm_gtin_code',
                        'value' => $product->gitn
                    ]
                ]
            ];


            $salePrice = (float) $product->sale_price;

            if ($salePrice > 0) {
                $payload['sale_price'] = (string) $salePrice;

                $now = Carbon::now();

                $start = $product->sales_start ? Carbon::parse($product->sales_start) : null;
                $end   = $product->sales_end ? Carbon::parse($product->sales_end) : null;
                // verifică dacă promoția nu este expirată
                if (!$end || $end->greaterThan($now)) {

                    $payload['sale_price'] = (string)$product->sale_price;

                    if ($start) {
                        $payload['date_on_sale_from'] = $start->toIso8601String();
                    }

                    if ($end) {
                        $payload['date_on_sale_to'] = $end->toIso8601String();
                    }
                }
            }
            $response = $this->client()->post(
                $this->baseUrl . '/wp-json/wc/v3/products',
                $payload
            );

            if ($response->failed()) {

                Log::error('Woo product creation failed', [
                    'sku' => $product->sku,
                    'response' => $response->json()
                ]);

                return ['status' => 'create_failed'];
            }

            $woo = $response->json();

            $product->update([
                'woo_product_id' => $woo['id'],
                'woo_parent_id' => null
            ]);

            return [
                'status' => 'created',
                'woo_id' => $woo['id']
            ];

        } catch (\Throwable $e) {

            Log::error('Woo product create exception', [
                'sku' => $product->sku,
                'error' => $e->getMessage()
            ]);

            return ['status' => 'exception'];
        }
    }

    public function fetchWooProduct(Product $product): ?array
    {
        $wooId = $product->woo_product_id;

        if (!$wooId) {
            return null;
        }

        try {

            $endpoint = $this->buildEndpoint($product, $wooId);

            $getResponse = $this->client()->get(
                $this->baseUrl . $endpoint
            );

            if ($getResponse->failed()) {
                return null;
            }

            return $getResponse->json();

        } catch (\Throwable $e) {

            Log::warning('Woo fetch failed', [
                'sku' => $product->sku,
                'woo_id' => $wooId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}