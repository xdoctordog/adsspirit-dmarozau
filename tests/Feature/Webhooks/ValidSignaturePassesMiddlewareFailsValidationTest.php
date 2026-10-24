<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ValidSignaturePassesMiddlewareFailsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signature_passes_middleware_but_fails_validation(): void
    {
        // 1. Get the secret from config
        $secret = config('services.marketplace.webhook_secret');

        // 2. Create a fake payload (JSON)
        $payload = [
            'id' => 'evt_01JAXM4Q9T6V2K8R3N5W7Y0ZBC',
            'type' => 'order.paid',
            'created_at' => '2026-10-21T10:05:48+00:00',
            'data' => [
                'order' => ['id' => 'ord_123']
            ]
        ];

        // 3. Encode payload to JSON exactly as it will be sent
        $jsonPayload = json_encode($payload);

        // 4. Generate timestamp (current time)
        $timestamp = time();

        // 5. Generate the signature (Simulating MarketHub)
        $payloadToSign = $timestamp . '.' . $jsonPayload;
        $signature = 'sha256=' . hash_hmac('sha256', $payloadToSign, $secret);

        // 6. Send the POST request to our app
        // Note: We use $this->call() or $this->post() with raw content to bypass Laravel's automatic JSON decoding
        $response = $this->call(
            'POST',
            '/api/webhooks/marketplace',
            [], // parameters (empty)
            [], // cookies (empty)
            [], // files (empty)
            [
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
                'HTTP_X_WEBHOOK_ID' => $payload['id'],
                'CONTENT_TYPE' => 'application/json',
            ],
            $jsonPayload // Raw body
        );

        // 7. Assert that the response is 200 OK
        $response->assertStatus(422);
    }
}
