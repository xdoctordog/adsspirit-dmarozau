<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $rate = $this->attributes['rate'] ?? 1.0;

        $basePrice = $this->faker->randomFloat(2, 100, 1000);
        $price = number_format($basePrice / $rate, 2, '.', '');

        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'sku' => strtoupper($this->faker->bothify('SKU-????')),
            'name' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'price' => (string) $price,
            'subtotal' => (string) ($price * $quantity),
        ];
    }

    /**
     * Remove the 'rate' attribute from the model before saving.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (OrderItem $item) {
            // Unset the dynamic attribute so it doesn't try to save into the DB
            $item->offsetUnset('rate');
        });
    }
}
