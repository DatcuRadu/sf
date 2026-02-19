<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

        // 🔄 Facem update
        $response = $this->client()->put(
            $this->baseUrl . '/wp-json/wc/v3/products/' . $product['id'],
            $updateData
        );

        return [
            'status' => 'updated',
            'updated_fields' => $updateData,
            'response' => $response->json()
        ];
    }
}
