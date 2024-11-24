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
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending_payment');
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->nullable();

            // coupon
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('original_amount', 10, 2)->nullable();

            // account-free checkout
            $table->string('email')->nullable();
            $table->string('nickname')->nullable();
            $table->string('tel')->nullable();
            $table->string('address')->nullable();
            $table->string('password')->nullable();

            // payment gateway
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_code')->nullable();
            
            $table->text('remark')->nullable();
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
