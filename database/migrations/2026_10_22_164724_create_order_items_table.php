<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Foreign key linking to the orders table
            // If the order is deleted, its items are automatically deleted (cascade)
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Item details from the OpenAPI `items` array
            $table->string('sku');
            $table->string('name');
            $table->integer('quantity');

            // Financial values (prices should always be decimals, not floats)
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
