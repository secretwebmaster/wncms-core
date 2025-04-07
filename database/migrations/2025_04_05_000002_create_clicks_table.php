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
        if (!Schema::hasTable('clicks')) {
            Schema::create('clicks', function (Blueprint $table) {
                $table->id();
                $table->morphs('clickable');
                $table->foreignId('channel_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('value')->nullable();
                $table->string('ip')->nullable();
                $table->string('referer')->nullable();
                $table->text('parameters')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
