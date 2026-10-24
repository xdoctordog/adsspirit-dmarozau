<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'order_id',
        'status',
        'at',
    ];

    // Attribute casting for correct data types
    protected $casts = [
        // Automatically cast the string from DB to the OrderStatus Enum
        'status' => OrderStatus::class,
        // Ensure the date is treated as a Carbon instance
        'at' => 'datetime',
    ];

    /**
     * Relationship: A history record belongs to a specific order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
