<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWooWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('x-wc-webhook-signature');

        if (!$signature) {
            return response()->json([
                'error' => 'Missing webhook signature'
            ], 401);
        }

        $secret = config('services.woo.secret');

        if (!$secret) {
            return response()->json([
                'error' => 'Webhook secret not configured'
            ], 500);
        }

        $computedSignature = base64_encode(
            hash_hmac(
                'sha256',
                $request->getContent(),
                $secret,
                true
            )
        );

        if (!hash_equals($computedSignature, $signature)) {
            return response()->json([
                'error' => 'Invalid webhook signature'
            ], 401);
        }

        return $next($request);
    }
}
