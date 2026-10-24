<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $currency = $this->faker->randomElement(Currency::cases());
        $rate = $currency->rate();

        // 3. Generate base amounts in RUB
        $baseDelivery = $this->faker->randomFloat(2, 0, 1500);
        $baseDiscount = $this->faker->randomFloat(2, 0, 50);
        $baseTotal = $this->faker->randomFloat(2, 50, 1500);

        // Logical check: Discount cannot exceed the total amount
        if ($baseDiscount > $baseTotal) {
            $baseDiscount = $baseTotal;
        }

        // 4. Convert RUB amounts to the chosen currency and format to 2 decimals (string)
        // Formula: Amount in RUB / Rate = Amount in foreign currency
        $deliveryPrice = number_format($baseDelivery / $rate, 2, '.', '');
        $discount = number_format($baseDiscount / $rate, 2, '.', '');
        $total = number_format($baseTotal / $rate, 2, '.', '');

        // 5. Randomize Order Status using Enum cases
        $status = $this->faker->randomElement(OrderStatus::cases());

        // 6. Initialize dependent fields
        $paidAt = null;
        $cancelledAt = null;
        $cancelReason = null;
        $shipmentCarrier = null;
        $shipmentTrackingNumber = null;
        $shipmentTrackingUrl = null;

        // Logic for paid_at (If paid, shipped, delivered, or cancelled after payment)
        if (in_array($status, [OrderStatus::PAID, OrderStatus::SHIPPED, OrderStatus::DELIVERED, OrderStatus::CANCELLED])) {
            $paidAt = $this->faker->dateTimeBetween('-1 month', 'now');
        }

        // Logic for cancelled_at and cancel_reason
        if ($status === OrderStatus::CANCELLED) {
            // Cancelled date must be after paid date (or after creation if not paid)
            $cancelledAt = $this->faker->dateTimeBetween($paidAt ?? '-1 month', 'now');
            $cancelReason = $this->faker->randomElement(['Клиент передумал', 'Нет в наличии', 'Ошибка оплаты']);
        }

        // Logic for shipment (Only for shipped or delivered)
        if (in_array($status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
            $shipmentCarrier = $this->faker->randomElement(['СДЭК', 'Почта России', 'Boxberry', 'DHL']);
            $shipmentTrackingNumber = $this->faker->numerify('##########');
            $shipmentTrackingUrl = 'https://tracking.example.com/' . $shipmentTrackingNumber;
        }

        // Determine creation date (must be before paid_at, etc.)
        $createdAt = $this->faker->dateTimeBetween('-3 months', '-1 day');

        return [
            'marketplace_id' => 'ord_' . $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'number' => 'MH-' . $this->faker->numberBetween(1000000, 9999999),
            'status' => $status,
            'currency' => $currency->value,

            'customer_id' => Customer::factory(),

            'delivery_price' => $deliveryPrice,
            'discount' => $discount,
            'total' => $total,

            'shipment_carrier' => $shipmentCarrier,
            'shipment_tracking_number' => $shipmentTrackingNumber,
            'shipment_tracking_url' => $shipmentTrackingUrl,

            'paid_at' => $paidAt,
            'cancelled_at' => $cancelledAt,
            'cancel_reason' => $cancelReason,

            'created_at' => $createdAt,
            'updated_at' => $cancelledAt ?? $paidAt ?? $createdAt,
        ];
    }

    /**
     * Configure the factory to automatically create related models.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {

            // 1. Convert string back to Enum to get the rate
            $currency = Currency::from($order->currency);
            $rate = $currency->rate();

            // 2. Create items passing the rate
            $itemCount = rand(1, 4);
            OrderItem::factory()->count(rand(1, $itemCount))->create([
                'order_id' => $order->id,
                'rate' => $rate,
            ]);

            $itemsSubtotal = $order->items()->sum('subtotal');
            $finalTotal = $itemsSubtotal + $order->delivery_price - $order->discount;
            $order->update([
                'total' => (string) $finalTotal,
            ]);

            // 2. Build Status History based on the final status
            $statusesToCreate = [OrderStatus::CREATED]; // Always has 'created'

            if (in_array($order->status, [OrderStatus::PAID, OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
                $statusesToCreate[] = OrderStatus::PAID;
            }
            if (in_array($order->status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
                $statusesToCreate[] = OrderStatus::SHIPPED;
            }
            if ($order->status === OrderStatus::DELIVERED) {
                $statusesToCreate[] = OrderStatus::DELIVERED;
            }
            if ($order->status === OrderStatus::CANCELLED) {
                $statusesToCreate[] = OrderStatus::CANCELLED;
            }

            // Create history records chronologically
            $timeCursor = Carbon::parse($order->created_at);
            foreach ($statusesToCreate as $status) {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $status,
                    'at' => $timeCursor,
                ]);

                // Add a few hours for the next status in history
                $timeCursor = $timeCursor->addHours(rand(1, 12));
            }
        });
    }
}
