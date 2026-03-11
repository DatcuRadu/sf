<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\Epicor\EstuOrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
                'error' => 'Webhook processing failed'
            ], 200);
        }
    }
}
