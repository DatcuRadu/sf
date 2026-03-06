<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;

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

    public function show(Product $product)
    {
        return $product->load(
            'histories',
            'syncLogs'
        );
    }
}