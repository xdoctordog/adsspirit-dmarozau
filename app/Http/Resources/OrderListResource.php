<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->marketplace_id,
            'number' => $this->number,
            // Enum automatically serializes to its string value
            'status' => $this->status,
            'customer' => [
                'email' => $this->customer->email,
                'name' => $this->customer->name,
            ],
            // Decimal cast ensures string with 2 decimal places
            'total' => $this->total,
            'currency' => $this->currency,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
