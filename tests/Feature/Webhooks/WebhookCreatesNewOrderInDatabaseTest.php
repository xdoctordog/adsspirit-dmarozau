<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebhookCreatesNewOrderInDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_new_order_in_database(): void
    {
        $secret = config('services.marketplace.webhook_secret');
        $timestamp = time();

        // 1. Create a realistic payload based on the OpenAPI and Webhook docs
        $payload = [
            'id' => 'evt_01JAXKZB7M2D9F4H6J8K0N3P5R',
            'type' => 'order.created',
            'created_at' => '2026-10-21T10:02:12+00:00',
            'data' => [
                'order' => [
                    'id' => 'ord_7Q2KX9M4',
                    'number' => 'MH-1048213',
                    'status' => 'created',
                    'currency' => 'RUB',
                    'customer' => [
                        'email' => 'anna.smirnova@example.com',
                        'name' => 'Анна Смирнова',
                        'phone' => '+79161234567',
                    ],
                    'items' => [
                        [
                            'sku' => 'TSHIRT-BLK-M',
                            'name' => 'Футболка базовая, чёрная, M',
                            'quantity' => 2,
                            'price' => '1290.00',
                            'subtotal' => '2580.00',
                        ]
                    ],
                    'delivery_price' => '500.00',
                    'discount' => '0.00',
                    'total' => '2580.00',
                    'shipment' => null,
                    'paid_at' => null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                    'created_at' => '2026-10-21T10:04:15+00:00',
                    'updated_at' => '2026-10-21T11:05:25+00:00',
                    'history' => [
                        [
                            'status' => 'created',
                            'at' => '2026-10-21T10:04:15+00:00',
                        ]
                    ],
                ]
            ]
        ];

        $jsonPayload = json_encode($payload);

        // 2. Sign the payload
        $payloadToSign = $timestamp . '.' . $jsonPayload;
        $signature = 'sha256=' . hash_hmac('sha256', $payloadToSign, $secret);

        // 3. Send the request
        $response = $this->call(
            'POST',
            '/api/webhooks/marketplace',
            [],
            [],
            [],
            [
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
                'HTTP_X_WEBHOOK_ID' => $payload['id'],
                'CONTENT_TYPE' => 'application/json',
            ],
            $jsonPayload
        );

        $order = Order::where('marketplace_id', 'ord_7Q2KX9M4')->firstOrFail();

        // 4. Assert the response is 200 OK
        $response->assertStatus(200);

        // 5. Assert that the data actually reached the database!

        // Check if the Customer was created
        $this->assertDatabaseHas('customers', [
            'id' => $order->customer_id,
            'email' => 'anna.smirnova@example.com',
            'name' => 'Анна Смирнова',
            'phone' => '+79161234567',
        ]);

        // Check if the Order was created
        $this->assertDatabaseHas('orders', [
            'marketplace_id' => 'ord_7Q2KX9M4',
            'number' => 'MH-1048213',
            'status' => 'created',
            'currency' => 'RUB',

            'shipment_carrier' => null,
            'shipment_tracking_number' => null,
            'shipment_tracking_url' => null,

            'delivery_price' => '500.00',
            'discount' => '0.00',
            'total' => '2580.00',
            'paid_at' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'created_at' => '2026-10-21T10:04:15+00:00',
            'updated_at' => '2026-10-21T11:05:25+00:00',
        ]);

        // Check if the OrderItem was created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku' => 'TSHIRT-BLK-M',
            'name' => 'Футболка базовая, чёрная, M',
            'quantity' => 2,
            'price' => '1290.00',
            'subtotal' => '2580.00',
        ]);

        // Check if the Status History was created
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'created',
            'at' => '2026-10-21T10:04:15+00:00',
        ]);
    }
}
