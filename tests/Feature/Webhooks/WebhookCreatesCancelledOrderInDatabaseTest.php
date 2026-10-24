<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebhookCreatesCancelledOrderInDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_cancelled_order_in_database(): void
    {
        $secret = config('services.marketplace.webhook_secret');
        $timestamp = time();

        // 1. Create a realistic payload based on the OpenAPI and Webhook docs
        $payload = [
            'id' => 'evt_01JAXZ6E8G0J2L4N6Q8S0U2W4Y',
            'type' => 'order.cancelled',
            'created_at' => '2026-10-21T17:47:16+00:00',
            'data' => [
                'order' => [
                    'id' => 'ord_3HV8PW2C',
                    'number' => 'MH-1048377',
                    'status' => 'cancelled',
                    'currency' => 'RUB',
                    'customer' => [
                        'email' => 'i.lebedev@example.org',
                        'name' => 'Игорь Лебедев',
                        'phone' => null,
                    ],
                    'items' => [
                        [
                            'sku' => 'MUG-WHT-350',
                            'name' => 'Кружка керамическая, белая, 350 мл',
                            'quantity' => 2,
                            'price' => '649.00',
                            'subtotal' => '1298.00',
                        ]
                    ],
                    'delivery_price' => '0.00',
                    'discount' => '0.00',
                    'total' => '1298.00',

                    'shipment' => null,
                    'paid_at' => "2026-10-21T18:11:02+03:00",
                    'cancelled_at' => "2026-10-21T20:47:15+03:00",
                    'cancel_reason' => "Покупатель отказался от заказа",
                    "created_at" => "2026-10-21T18:09:40+03:00",
                    "updated_at" => "2026-10-21T20:47:15+03:00",
                    'history' => [
                        [
                            'status' => 'created',
                            'at' => '2026-10-21T17:47:16+00:00',
                        ],
                        [
                            'status' => 'paid',
                            'at' => '2026-10-21T17:49:16+00:00',
                        ],
                        [
                            'status' => 'cancelled',
                            'at' => '2026-10-21T17:55:16+00:00',
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
        $order = \App\Models\Order::where('marketplace_id', 'ord_3HV8PW2C')->firstOrFail();

        // 4. Assert the response is 200 OK
        $response->assertStatus(200);

        // 5. Assert that the data actually reached the database!

        // Check if the Customer was created
        $this->assertDatabaseHas('customers', [
            'id' => $order->customer_id,
            'email' => 'i.lebedev@example.org',
            'name' => 'Игорь Лебедев',
            'phone' => null,
        ]);

        // Check if the Order was created
        $this->assertDatabaseHas('orders', [
            'marketplace_id' => 'ord_3HV8PW2C',
            'number' => 'MH-1048377',
            'status' => 'cancelled',
            'currency' => 'RUB',

            'delivery_price' => '0.00',
            'discount' => '0.00',
            'total' => '1298.00',

            'shipment_carrier' => null,
            'shipment_tracking_number' => null,
            'shipment_tracking_url' => null,

            'paid_at' => "2026-10-21T15:11:02",
            'cancelled_at' => "2026-10-21T17:47:15",
            'cancel_reason' => "Покупатель отказался от заказа",

            "created_at" => "2026-10-21T15:09:40",
            "updated_at" => "2026-10-21T17:47:15",
         ]);

        // Check if the OrderItem was created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku' => 'MUG-WHT-350',
            'name' => 'Кружка керамическая, белая, 350 мл',
            'quantity' => 2,
            'price' => '649.00',
            'subtotal' => '1298.00',
        ]);

        // Check if the Statuses History were created
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'created',
            'at' => '2026-10-21T17:47:16+00:00',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'paid',
            'at' => '2026-10-21T17:49:16+00:00',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'cancelled',
            'at' => '2026-10-21T17:55:16+00:00',
        ]);
    }
}
