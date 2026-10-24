<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ProcessedWebhook;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMarketplaceSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signatureHeader = $request->header('X-Webhook-Signature');
        $timestampHeader = $request->header('X-Webhook-Timestamp');
        $webhookId = $request->header('X-Webhook-Id');
        $secret = config('services.marketplace.webhook_secret');

        // 1. Check if all required headers exist
        if (!$signatureHeader || !$timestampHeader || !$webhookId || !$secret) {
            return response()->json(['error' => 'Missing required headers or secret'], 401);
        }

        // 2. Validate timestamp is numeric and not too old (5 minutes max)
        if (!is_numeric($timestampHeader)) {
            return response()->json(['error' => 'Invalid timestamp format'], 401);
        }
        $currentTime = time();
        if (abs($currentTime - (int)$timestampHeader) > 60 * 5) {
            return response()->json(['error' => 'Signature timestamp is too old'], 401);
        }

        // 3. Get raw body (MUST NOT use $request->all() as it alters formatting)
        $rawBody = $request->getContent();

        // 4. Calculate expected signature using HMAC-SHA256
        $payloadToSign = $timestampHeader . '.' . $rawBody;
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payloadToSign, $secret);

        // 5. Compare using hash_equals to prevent timing attacks
        if (!hash_equals($expectedSignature, $signatureHeader)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 6. Validate JSON format
        $payload = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        // 7. Verify that X-Webhook-Id matches the id inside the JSON body
        if (!isset($payload['id']) || $payload['id'] !== $webhookId) {
            return response()->json(['error' => 'Webhook ID mismatch'], 401);
        }

        if (ProcessedWebhook::where('webhook_id', $webhookId)->exists()) {
            // Event already processed, return early with 200 OK to stop retries
            return response()->json(['status' => 'already_processed']);
        }

        return $next($request);
    }
}
