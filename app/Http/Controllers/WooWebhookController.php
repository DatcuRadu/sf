<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\Epicor\EstuOrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;
use App\Models\WcOrder;

class WooWebhookController extends Controller
{
    public function handle(
        Request $request,
        EstuOrderService $estuService
    ): JsonResponse {

        try {


            $filename = 'debug_' . Carbon::now()->format('Ymd_His') . '.json';

            $data = [
                'method'  => $request->method(),
                'url'     => $request->fullUrl(),
                'ip'      => $request->ip(),
                'headers' => $request->headers->all(),
                'query'   => $request->query(),
                'body'    => $request->getContent(),
                'json'    => $request->all(),
            ];

            Storage::disk('local')->put(
                'debug_order/' . $filename,
                json_encode($data, JSON_PRETTY_PRINT)
            );

            // ================= VALIDARE STRUCTURĂ =================
            $validated = $request->validate([
                'id' => 'required|integer',
                'number' => 'required|string',
                'status' => 'required|string',
                'total' => 'required|numeric',
                'line_items' => 'required|array|min:1',
                'date_created' => 'nullable|string',
            ]);

            // ================= LOG RECEPȚIE =================
            Log::info('Woo webhook received', [
                'order_id' => $validated['id'],
                'status' => $validated['status']
            ]);

            // ================= STATUS CHECK =================

            $allowedStatuses = ['processing', 'completed'];

            if (!in_array($validated['status'], $allowedStatuses, true)) {
                Log::info('Order skipped due to status', [
                    'order_id' => $validated['id'],
                    'status'   => $validated['status']
                ]);

                return response()->json([
                    'message' => 'Order status not allowed'
                ], 202);
            }

            // ================= IDEMPOTENCY =================
//            if ($estuService->alreadyProcessed($validated['id'])) {
//                return response()->json([
//                    'message' => 'Order already processed'
//                ], 200);
//            }

            // ================= GENERARE ESTU =================
            $file = $estuService->generateFromWoo($request->all());

            $this->store($request);

            Log::info('ESTU generated successfully', [
                'order_id' => $validated['id'],
                'file' => $file
            ]);

            return response()->json([
                'status' => 'success',
                'file' => $file
            ], 200);

        } catch (Throwable $e) {

            Log::error('Woo webhook failed', [
                'error' => $e->getMessage(),
                'order_id' => $request->id ?? null
            ]);

            return response()->json([
                'error' => print_r($e->getMessage(),true)
            ], 200);
        }
    }


    public function store(Request $request): JsonResponse
    {
        try {

            $data = $request->json()->all();

            $order = WcOrder::updateOrCreate(
                ['wc_order_id' => $data['id']],
                [
                    'order_number' => $data['number'] ?? null,
                    'status'       => $data['status'] ?? null,
                    'total'        => $data['total'] ?? null,
                    'currency'     => $data['currency'] ?? null,
                    'billing'      => $data['billing'] ?? [],
                    'shipping'     => $data['shipping'] ?? [],
                    'raw_payload'  => $data,
                    'epicor_status'=> 'pending'
                ]
            );

            return response()->json([
                'status' => 'saved',
                'order_id' => $order->id
            ]);

        } catch (\Throwable $e) {

            Log::error('Order save failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => print_r( $e->getMessage(), true)
            ], 500);
        }
    }


    public function generate($id, EstuOrderService $estuService)
    {
        $order = WcOrder::findOrFail($id);

        $file = $estuService->generateFromWoo($order->raw_payload);

        $order->update([
            'epicor_status' => 'processing'
        ]);

        return response()->json([
            'status' => 'generated',
            'file' => $file
        ]);
    }

}
