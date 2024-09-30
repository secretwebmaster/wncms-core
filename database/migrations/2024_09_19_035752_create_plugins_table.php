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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_id')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('url')->nullable();
            $table->string('author')->nullable();
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('inactive');
            $table->string('path');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
