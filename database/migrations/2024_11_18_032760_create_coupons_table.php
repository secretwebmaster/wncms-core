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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('code')->unique();
            $table->string('type')->default('fixed'); // fixed, percentage
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->integer('limit')->default(1);
            $table->integer('used')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
