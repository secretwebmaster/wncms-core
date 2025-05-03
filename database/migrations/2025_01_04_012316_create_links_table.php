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
        if (!Schema::hasTable('links')) {
            Schema::create('links', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('active');
                $table->string('slug');
                $table->string('name');
                $table->string('url');
                $table->text('description')->nullable();
                $table->string('external_thumbnail')->nullable();
                $table->integer('clicks')->nullable()->default(0);
                $table->string('remark')->nullable();

                $table->integer('order')->nullable();
                $table->string('color')->nullable();
                $table->boolean('is_pinned')->nullable()->default(false);
                $table->datetime('expired_at')->nullable();

                $table->string('tracking_code')->nullable()->index();
                $table->string('slogan')->nullable();
                $table->string('background')->nullable();
                $table->string('contact')->nullable();
                $table->boolean('is_recommended')->nullable()->default(false);
                $table->datetime('hit_at')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
