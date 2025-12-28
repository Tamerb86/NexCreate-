<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('creator_id')->constrained('users')->onDelete('restrict');
            $table->string('stripe_payment_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->decimal('creator_earning', 10, 2);
            $table->string('currency', 3)->default('nok');
            $table->enum('status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('available_at')->nullable(); // When earnings become available for payout
            $table->timestamps();

            // Indexes
            $table->index(['buyer_id', 'status']);
            $table->index(['creator_id', 'status']);
            $table->index('stripe_payment_id');
            $table->index('stripe_checkout_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
