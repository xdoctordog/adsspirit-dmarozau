<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->marketplace_id,
            'number' => $this->number,
            'status' => $this->status,
            'customer' => [
                'email' => $this->customer->email,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ],
            'currency' => $this->currency,
            'items' => $this->items->map(fn($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ]),
            'delivery_price' => $this->delivery_price,
            'discount' => $this->discount,
            'total' => $this->total,

            'shipment' => $this->shipment_carrier ? [
                'carrier' => $this->shipment_carrier,
                'tracking_number' => $this->shipment_tracking_number,
                'tracking_url' => $this->shipment_tracking_url,
            ] : null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'history' => $this->statusHistories->map(fn($h) => [
                'status' => $h->status,
                'at' => $h->at->toIso8601String(),
            ]),
        ];
    }
}
