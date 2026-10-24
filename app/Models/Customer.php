<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = [
        'email',
        'name',
        'phone',
    ];

    // Relationship: A customer can have many orders (1:N)
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
