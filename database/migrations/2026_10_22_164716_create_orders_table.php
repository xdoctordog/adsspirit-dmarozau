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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Marketplace order ID (matches `id` in OpenAPI). Unique to prevent duplicates.
            $table->string('marketplace_id')->unique();

            // Customer-facing order number
            $table->string('number');
            // Current status (enum: created, paid, shipped, delivered, cancelled)
            $table->string('status')->index();

            // Foreign key linking to the customers table.
            // The constrained() method automatically infers the 'customers' table.
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

            $table->string('currency', 10);

            // Financial totals (OpenAPI provides strings, DB uses decimals)
            $table->decimal('delivery_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Shipment details (from `shipment` object, can be null)
            $table->string('shipment_carrier')->nullable();
            $table->string('shipment_tracking_number')->nullable();
            $table->text('shipment_tracking_url')->nullable();

            // Timestamps from webhook payload
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            // Standard Laravel timestamps (we will override these with webhook data)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
