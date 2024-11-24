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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->string('type'); // virtual, physical
            $table->decimal('price', 10, 2);
            $table->integer('stock')->nullable();
            $table->boolean('is_variable')->default(false);
            $table->json('attributes')->nullable(); // Fixed {"version": "1.0", "color": "red"}
            $table->json('variants')->nullable(); // Selectable {"color": ["red", "blue"], "size": ["s", "m", "l"]}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
