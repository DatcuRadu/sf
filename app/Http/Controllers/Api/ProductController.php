<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Services\WooCommerceProductSyncService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // 🔍 SEARCH SKU / NAME
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('sku', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");

            });

        }

        // 📌 FILTER deleted
        if ($request->deleted !== null) {

            $query->where('deleted', $request->deleted);

        }

        // 📌 FILTER to_sync
        if ($request->to_sync !== null) {

            $query->where('to_sync', $request->to_sync);

        }

        // 🗓 ORDER
        $query->orderBy('updated_at', 'desc');

        // 📄 PAGINATION
        $products = $query->paginate(1000);

        return ProductResource::collection($products);
    }

    public function show(Product $product, WooCommerceProductSyncService $wooService)
    {
        $product->load([
            'histories' => fn ($q) => $q->latest(),
            'syncLogs' => fn ($q) => $q->latest(),
        ]);

        // luam produsul real din Woo
        $wooProduct = $wooService->fetchWooProduct($product);



        // returnam produsul normal + woo
        $data = $product->toArray();
        $data['woo_product'] = $wooProduct;


        if ($wooProduct) {

            $base = rtrim(config('woocommerce.url'), '/');

            // dacă este variation → deschidem parent product
            $editId = $product->woo_parent_id ?: $product->woo_product_id;

            $data['woo_product']['admin_edit_url'] =
                $base . '/wp-admin/post.php?post=' . $editId . '&action=edit&variation='. $product->woo_product_id;
        }

        return response()->json($data);
    }
}