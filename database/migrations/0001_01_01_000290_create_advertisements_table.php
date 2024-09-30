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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // active | paused | suspended
            $table->datetime('expired_at')->nullable();
            $table->string('name')->nullable();
            $table->string('type'); // text | image | card | script
            $table->string('cta_text')->nullable();
            $table->string('url')->nullable();
            $table->string('cta_text_2')->nullable();
            $table->string('url_2')->nullable();
            $table->string('remark')->nullable();
            $table->string('text_color')->nullable();
            $table->string('background_color')->nullable();
            $table->text('code')->nullable();
            $table->text('style')->nullable();
            $table->string('position')->nullable();
            $table->integer('order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
