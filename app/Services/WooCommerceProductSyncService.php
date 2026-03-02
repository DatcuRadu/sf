<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WooCommerceSyncLog;

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

    public function client()
    {
        return Http::withBasicAuth($this->key, $this->secret)
            ->acceptJson();
    }

    public function findBySku(string $sku)
    {
        $response = $this->client()->get(
            $this->baseUrl . '/wp-json/wc/v3/products',
            ['sku' => $sku]
        );

        return $response->json();
    }

    public function findByGtin(string $gtin)
    {
        $response = $this->client()->get(
            $this->baseUrl . '/wp-json/wc/v3/products',
            [
                'meta_key' => '_wpm_gtin_code',
                'meta_value' => $gtin,
            ]
        );

        return $response->json();
    }

    public function syncByProductId(
        int $productId,
        float $regularPrice,
        float $salePrice,
        int $qty,
        ?string $saleStart,
        ?string $saleEnd
    ): array {

        $requestPayload = [

            'regular_price' => $regularPrice,
            'sale_price'    => $salePrice,
            'qty'           => $qty,
            'sale_start'    => $saleStart,
            'sale_end'      => $saleEnd,
        ];

        // 🔎 1️⃣ GET produs
        $response = $this->client()->get(
            $this->baseUrl . '/wp-json/wc/v3/products/' . $productId
        );

        if ($response->failed()) {

            WooCommerceSyncLog::create([
                'woocommerce_product_id' => $productId,
                'sku' => 'auto',
                'status' => 'not_found_by_id',
                'request_payload' => $requestPayload,
                'response_payload' => $response->json(),
            ]);

            return ['status' => 'not_found_by_id'];
        }

        $product = $response->json();

        $updateData = [];
        $needsUpdate = false;

        // 🔍 Comparări
        if ((float)$product['regular_price'] !== $regularPrice) {
            $updateData['regular_price'] = (string)$regularPrice;
            $needsUpdate = true;
        }

        if ((float)$product['sale_price'] !== $salePrice) {
            $updateData['sale_price'] = (string)$salePrice;
            $needsUpdate = true;
        }

        if ((int)$product['stock_quantity'] !== $qty) {
            $updateData['stock_quantity'] = $qty;
            $updateData['manage_stock'] = true;
            $needsUpdate = true;
        }

        if ($product['date_on_sale_from'] !== $saleStart) {
            $updateData['date_on_sale_from'] = $saleStart;
            $needsUpdate = true;
        }

        if ($product['date_on_sale_to'] !== $saleEnd) {
            $updateData['date_on_sale_to'] = $saleEnd;
            $needsUpdate = true;
        }

        // 📌 Dacă nu sunt modificări
        if (!$needsUpdate) {

            WooCommerceSyncLog::create([
                'woocommerce_product_id' => $productId,
                'sku' => 'auto',
                'status' => 'no_changes',
                'old_data' => [
                    'regular_price' => $product['regular_price'],
                    'sale_price' => $product['sale_price'],
                    'stock_quantity' => $product['stock_quantity'],
                    'date_on_sale_from' => $product['date_on_sale_from'],
                    'date_on_sale_to' => $product['date_on_sale_to'],
                ],
                'request_payload' => $requestPayload,
            ]);

            return ['status' => 'no_changes'];
        }

        // 🔄 UPDATE
        $updateResponse = $this->client()->put(
            $this->baseUrl . '/wp-json/wc/v3/products/' . $productId,
            $updateData
        );

        $status = $updateResponse->successful() ? 'updated' : 'update_failed';

        WooCommerceSyncLog::create([
            'woocommerce_product_id' => $productId,
            'status' => $status,
            'sku' => 'auto',
            'old_data' => [
                'regular_price' => $product['regular_price'],
                'sale_price' => $product['sale_price'],
                'stock_quantity' => $product['stock_quantity'],
                'date_on_sale_from' => $product['date_on_sale_from'],
                'date_on_sale_to' => $product['date_on_sale_to'],
            ],
            'new_data' => $updateData,
            'request_payload' => $requestPayload,
            'response_payload' => $updateResponse->json(),
        ]);

        return [
            'status' => $status,
            'updated_fields' => $updateData,
        ];
    }

    public function sync(
        string $sku,
        ?string $gtin,
        float $regularPrice,
        float $salePrice,
        int $qty,
        ?string $saleStart,
        ?string $saleEnd
    ): array {

        // 🔎 1. Caută produs
        $products = $this->findBySku($sku);

        if (empty($products) && $gtin) {
            $products = $this->findByGtin($gtin);
        }

        if (empty($products)) {
            WooCommerceSyncLog::create([
                'sku' => $sku,
                'status' => 'not_found',
                'request_payload' => [
                    'regular_price' => $regularPrice,
                    'sale_price' => $salePrice,
                    'qty' => $qty,
                    'sale_start' => $saleStart,
                    'sale_end' => $saleEnd,
                ],
            ]);

            return ['status' => 'not_found'];
        }

        $product = $products[0];
        $updateData = [];
        $needsUpdate = false;

        // 🔍 Comparăm preț normal
        if ((float)$product['regular_price'] !== $regularPrice) {
            $updateData['regular_price'] = (string)$regularPrice;
            $needsUpdate = true;
        }

        // 🔍 Comparăm sale price
        if ((float)$product['sale_price'] !== $salePrice) {
            $updateData['sale_price'] = (string)$salePrice;
            $needsUpdate = true;
        }

        // 🔍 Comparăm qty
        if ((int)$product['stock_quantity'] !== $qty) {
            $updateData['stock_quantity'] = $qty;
            $updateData['manage_stock'] = true;
            $needsUpdate = true;
        }

        // 🔍 Comparăm sale start
        if ($product['date_on_sale_from'] !== $saleStart) {
            $updateData['date_on_sale_from'] = $saleStart;
            $needsUpdate = true;
        }

        // 🔍 Comparăm sale end
        if ($product['date_on_sale_to'] !== $saleEnd) {
            $updateData['date_on_sale_to'] = $saleEnd;
            $needsUpdate = true;
        }

        if (!$needsUpdate) {
            return ['status' => 'no_changes'];
        }

        $oldData = [
            'regular_price' => $product['regular_price'],
            'sale_price' => $product['sale_price'],
            'stock_quantity' => $product['stock_quantity'],
            'date_on_sale_from' => $product['date_on_sale_from'],
            'date_on_sale_to' => $product['date_on_sale_to'],
        ];

        // 🔄 Facem update
        $response = $this->client()->put(
            $this->baseUrl . '/wp-json/wc/v3/products/' . $product['id'],
            $updateData
        );

        WooCommerceSyncLog::create([
            'sku' => $sku,
            'woocommerce_product_id' => $product['id'],
            'status' => 'updated',
            'old_data' => $oldData,
            'new_data' => $updateData,
            'request_payload' => [
                'regular_price' => $regularPrice,
                'sale_price' => $salePrice,
                'qty' => $qty,
                'sale_start' => $saleStart,
                'sale_end' => $saleEnd,
            ],
            'response_payload' => $response->json(),
        ]);


        return [
            'status' => 'updated',
            'updated_fields' => $updateData,
            'response' => $response->json()
        ];
    }
}
