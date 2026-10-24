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
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();

            // Foreign key linking to the orders table
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // The status value (created, paid, shipped, etc.)
            $table->string('status');

            // The exact time of the status change.
            // We use 'at' here to exactly match the OpenAPI schema.
            $table->timestamp('at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
