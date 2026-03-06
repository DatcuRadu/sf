<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WcOrder;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WcOrder::query();

        // 🔍 SEARCH
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                    ->orWhere('wc_order_id', 'like', "%{$request->search}%");
            });
        }

        // 📌 STATUS FILTER
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // 🗓 ORDERING
        $query->orderBy('created_at', 'desc');

        // 📄 PAGINATION
        $orders = $query->paginate(20);

        return OrderResource::collection($orders);
    }
}
