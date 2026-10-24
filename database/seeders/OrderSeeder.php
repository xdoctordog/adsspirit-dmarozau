<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    // Number of orders to generate
    private const ORDERS_COUNT = 50;

    public function run(): void
    {
        $orders = Order::factory()->count(self::ORDERS_COUNT)->create();
    }
}
