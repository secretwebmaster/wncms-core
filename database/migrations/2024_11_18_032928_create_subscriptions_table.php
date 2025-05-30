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
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('active')->change();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('price_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('subscribed_at');
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
