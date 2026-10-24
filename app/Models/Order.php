<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'customer_id',
        'marketplace_id',
        'number',
        'status',
        'currency',
        'delivery_price',
        'discount',
        'total',
        'shipment_carrier',
        'shipment_tracking_number',
        'shipment_tracking_url',
        'paid_at',
        'cancelled_at',
        'cancel_reason',
    ];

    // Attribute casting for correct data types
    protected $casts = [
        'status' => OrderStatus::class,
        'delivery_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Order belongs to a customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: An order has many items (products).
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship: An order has a chronological history of status changes.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('at');
    }
}
