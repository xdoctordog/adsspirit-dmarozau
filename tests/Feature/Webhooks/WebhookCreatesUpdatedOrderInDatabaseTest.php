<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebhookCreatesUpdatedOrderInDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_paid_order_in_database(): void
    {
        $secret = config('services.marketplace.webhook_secret');
        $timestamp = time();

        $marketPlaceOrderId = 'ord_7Q2KX9M4';
        $marketPlaceNumber = 'MH-1048213';

        // 1. Create a realistic payload based on the OpenAPI and Webhook docs
        $payload = [
            'id' => 'evt_01JAXM0C3T5V7X9Z1B3D5F7H9K',
            'type' => 'order.updated',
            'created_at' => '2026-10-21T10:03:31+00:00',
            'data' => [
                'order' => [
                    'id' => $marketPlaceOrderId,
                    'number' => $marketPlaceNumber,
                    'status' => 'created',
                    'currency' => 'RUB',
                    'customer' => [
                        'email' => 'anna.smirnova@example.com',
                        'name' => 'Анна Смирнова',
                        'phone' => "+79161234567",
                    ],
                    'items' => [
                        [
                            'sku' => 'TSHIRT-BLK-M',
                            'name' => 'Футболка базовая, чёрная, M',
                            'quantity' => 3,
                            'price' => '1290.00',
                            'subtotal' => '3870.00',
                        ],
                        [
                            'sku' => 'CAP-NVY',
                            'name' => 'Бейсболка \"Classic\" / синяя',
                            'quantity' => 1,
                            'price' => '890.50',
                            'subtotal' => '890.50',
                        ],
                    ],
                    'delivery_price' => '349.00',
                    'discount' => '200.00',
                    'total' => '4909.50',

                    'shipment' => null,

                    'paid_at' => null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                    "created_at" => "2026-10-21T13:02:11+03:00",
                    "updated_at" => "2026-10-21T13:03:30+03:00",
                    'history' => [
                        [
                            'status' => 'created',
                            'at' => '2026-10-21T13:02:11+03:00',
                        ],
                        [
                            'status' => 'created',
                            'at' => '2026-10-21T13:03:30+03:00',
                        ],
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

        // Find the order we just created to get its internal DB ID
        $order = Order::where('marketplace_id', $marketPlaceOrderId)->firstOrFail();

        // 4. Assert the response is 200 OK
        $response->assertStatus(200);

        // 5. Assert that the data actually reached the database!

        // Check if the Customer was created
        $this->assertDatabaseHas('customers', [
            'id' => $order->customer_id,
            'email' => 'anna.smirnova@example.com',
            'name' => 'Анна Смирнова',
            'phone' => "+79161234567",
        ]);

        // Check if the Order was created
        $this->assertDatabaseHas('orders', [
            'marketplace_id' => $marketPlaceOrderId,
            'number' => $marketPlaceNumber,
            'status' => 'created',
            'currency' => 'RUB',

            'delivery_price' => '349.00',
            'discount' => '200.00',
            'total' => '4909.50',

            'shipment_carrier' => null,
            'shipment_tracking_number' => null,
            'shipment_tracking_url' => null,

            'paid_at' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,

            "created_at" => "2026-10-21T10:02:11",
            "updated_at" => "2026-10-21T10:03:30",
         ]);

        // Check if the OrderItems were created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku' => 'TSHIRT-BLK-M',
            'name' => 'Футболка базовая, чёрная, M',
            'quantity' => 3,
            'price' => '1290.00',
            'subtotal' => '3870.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku' => 'CAP-NVY',
            'name' => 'Бейсболка \"Classic\" / синяя',
            'quantity' => 1,
            'price' => '890.50',
            'subtotal' => '890.50',
        ]);

        // Check if the Statuses History were created
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'created',
            'at' => '2026-10-21T10:02:11',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'created',
            'at' => '2026-10-21T10:03:30',
        ]);
    }
}
