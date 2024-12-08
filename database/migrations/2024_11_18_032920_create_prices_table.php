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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');
            $table->decimal('amount', 10, 2);
            $table->integer('duration')->nullable();
            $table->string('duration_unit')->nullable(); // day, week, month, year
            $table->json('attributes')->nullable(); // E.g., {"color": "Red", "size": "M"}
            $table->boolean('is_lifetime')->default(false);
            $table->integer('stock')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
