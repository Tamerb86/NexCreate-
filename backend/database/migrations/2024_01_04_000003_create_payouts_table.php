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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->string('stripe_payout_id')->nullable();
            $table->string('stripe_transfer_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['creator_id', 'status']);
            $table->index('stripe_payout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
