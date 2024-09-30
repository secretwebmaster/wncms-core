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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('active'); //active | paused
            $table->string('theme_id')->unique(); //slug
            $table->string('type');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('author')->nullable();
            $table->string('version')->default('1.0.0');
            $table->string('demo_url')->nullable();
            $table->string('license')->nullable();
            $table->datetime('author_created_at')->nullable();
            $table->datetime('author_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
