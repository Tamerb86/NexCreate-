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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('restrict');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('creator_id')->constrained('users')->onDelete('restrict');
            $table->decimal('price', 10, 2);
            $table->text('requirements')->nullable();
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'in_progress',
                'delivered',
                'completed',
                'cancelled'
            ])->default('pending');
            $table->timestamp('delivery_date')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['buyer_id', 'status']);
            $table->index(['creator_id', 'status']);
            $table->index('status');
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
