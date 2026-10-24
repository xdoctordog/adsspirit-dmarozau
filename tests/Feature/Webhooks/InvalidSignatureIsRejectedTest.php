<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvalidSignatureIsRejectedTest extends TestCase
{
     use RefreshDatabase;

    public function test_invalid_signature_is_rejected(): void
    {
        $jsonPayload = json_encode(['id' => 'evt_123']);
        $timestamp = time();

        // Generate a completely wrong signature
        $signature = 'sha256=wrong_signature_hash';

        $response = $this->call(
            'POST',
            '/api/webhooks/marketplace',
            [],
            [],
            [],
            [
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE' => 'application/json',
            ],
            $jsonPayload
        );

        // Assert that the request is rejected
        $response->assertStatus(401);
    }
}
