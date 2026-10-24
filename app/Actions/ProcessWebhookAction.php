<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProcessedWebhook;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessWebhookAction
{
    /**
     * Execute the webhook processing logic.
     */
    public function execute(array $payload, string $eventId): void
    {
        // Wrap in a transaction to ensure data integrity
        DB::transaction(function () use ($payload, $eventId) {

            // 1. Create or update the Customer based on email
            $customer = Customer::updateOrCreate(
                ['email' => $payload['customer']['email']],
                [
                    'name' => $payload['customer']['name'],
                    'phone' => $payload['customer']['phone'] ?? null,
                ]
            );

            // 2. Find and LOCK the Order row for update
            // lockForUpdate() tells the database: "No other transaction can read or modify this row until I am done"
            $order = Order::where('marketplace_id', $payload['id'])->lockForUpdate()->first();

            // If the order doesn't exist, create a new instance
            if (!$order) {
                $order = new Order();
                $order->marketplace_id = $payload['id'];
                $oldOrderStatus = null;
            } else {
                $oldOrderStatus = $order->status;
            }

            // Fill the order with the new data
            $order->fill([
                'customer_id' => $customer->id,
                'number' => $payload['number'],
                'status' => $payload['status'],
                'currency' => $payload['currency'],
                'delivery_price' => $payload['delivery_price'],
                'discount' => $payload['discount'],
                'total' => $payload['total'],
                'shipment_carrier' => $payload['shipment']['carrier'] ?? null,
                'shipment_tracking_number' => $payload['shipment']['tracking_number'] ?? null,
                'shipment_tracking_url' => $payload['shipment']['tracking_url'] ?? null,
                'paid_at' => !empty($payload['paid_at']) ? Carbon::parse($payload['paid_at'])->utc() : null,
                'cancelled_at' => !empty($payload['cancelled_at']) ? Carbon::parse($payload['cancelled_at'])->utc() : null,
                'cancel_reason' => $payload['cancel_reason'],
            ]);

            // Explicitly override the timestamps with the marketplace data
            if (isset($payload['created_at'])) {
                $order->created_at = Carbon::parse($payload['created_at'])->utc();
            }
            if (isset($payload['updated_at'])) {
                $order->updated_at = Carbon::parse($payload['updated_at'])->utc();
            }

            // Save the order (will execute INSERT or UPDATE)
            $order->save();

            // 3. Sync Order Items (delete old, insert new)
            $order->items()->delete();

            // Map payload items to our database structure
            $itemsToCreate = collect($payload['items'])->map(function ($item) {
                return [
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ];
            })->toArray();

            $order->items()->createMany($itemsToCreate);

            // 4. Sync Status History (Strict Sync)
            // Delete existing history and recreate it to match the webhook payload exactly
            $order->statusHistories()->delete();

            $historiesToCreate = collect($payload['history'])->map(function ($historyData) {
                return [
                    'status' => $historyData['status'],
                    'at' => Carbon::parse($historyData['at'])->utc(),
                ];
            })->toArray();

            $order->statusHistories()->createMany($historiesToCreate);

            // 5. Mark this event as processed
            $webhook = ProcessedWebhook::firstOrCreate(['webhook_id' => $eventId]);

            // 6. Queue Email (ONLY if status changed)
            $newOrderStatus = OrderStatus::from($payload['status']);
            if ($newOrderStatus !== $oldOrderStatus) {
                $this->scheduleNotification($order, $newOrderStatus);
            }
        });
    }

    /**
     * Schedule the email in the queue based on the new status.
     */
    private function scheduleNotification(Order $order, OrderStatus $status): void
    {
        // 1. We choose WHICH Mailable class to create based on the status
        $mailableObject = match ($status) {
            OrderStatus::PAID => new OrderPaidMail($order),
            OrderStatus::SHIPPED => new OrderShippedMail($order),
            OrderStatus::CANCELLED => new OrderCancelledMail($order),
            default => null,
        };

        if ($mailableObject) {
            // 2. We pass the $mailableObject object (not the status) to the queue
            Mail::to($order->customer->email)->queue($mailableObject);
        }
    }
}
