<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(array $attributes = []): Order
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'marketplace_id' => 'ord_test_123',
            'number' => 'MH-TEST-001',
            'status' => OrderStatus::PAID,
            'created_at' => now()->subDay(),
        ], $attributes));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'sku' => 'SKU-1',
            'name' => 'Item 1',
            'quantity' => 2,
            'price' => '100.00',
            'subtotal' => '200.00',
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => OrderStatus::PAID,
            'at' => now(),
        ]);

        return $order;
    }

    /**
     * Generate a specific number of dummy orders for testing.
     */
    private function createOrders(int $count = 25): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createOrder([
                // Override the hardcoded default values to ensure uniqueness
                'marketplace_id' => 'ord_test_' . $i,
                'number' => 'MH-TEST-' . $i,
            ]);
        }
    }

    public function test_can_list_orders_with_pagination(): void
    {
        $this->createOrder();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'status',
                        'customer' => [
                            'email',
                            'name'
                        ],
                        'total',
                        'currency',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    public function test_can_filter_orders_by_status(): void
    {
        $this->createOrder(['status' => OrderStatus::PAID, 'marketplace_id' => 'ord_1']);
        $this->createOrder(['status' => OrderStatus::SHIPPED, 'marketplace_id' => 'ord_2']);

        $response = $this->getJson('/api/orders?status=paid');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
    }

    public function test_can_filter_orders_by_email_case_insensitive(): void
    {
        $customer = Customer::factory()->create(['email' => 'anna@example.com']);
        Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson('/api/orders?email=ANNA@EXAMPLE.COM');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_order(): void
    {
        $order = $this->createOrder();

        $response = $this->getJson("/api/orders/{$order->marketplace_id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'status',
                    'customer' => [
                        'email',
                        'name',
                        'phone'
                    ],
                    'currency',
                    'items',
                    'delivery_price',
                    'discount',
                    'total',
                    'shipment',
                    'paid_at',
                    'cancelled_at',
                    'cancel_reason',
                    'created_at',
                    'updated_at',
                    'history'
                ]
            ]);
    }

    public function test_returns_404_for_non_existent_order(): void
    {
        $response = $this->getJson('/api/orders/unknown_id');
        $response->assertStatus(404);
    }

    public function test_per_page_is_limited_to_100(): void
    {
        $response = $this->getJson('/api/orders?per_page=500');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_returns_422_for_invalid_status_filter(): void
    {
        $response = $this->getJson('/api/orders?status=invalid_status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
