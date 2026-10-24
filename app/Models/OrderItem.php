<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'order_id',
        'sku',
        'name',
        'quantity',
        'price',
        'subtotal',
    ];

    // Attribute casting for correct data types
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relationship: An item belongs to a specific order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
