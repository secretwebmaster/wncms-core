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
        if (!Schema::hasTable('page_templates')) {
            Schema::create('page_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained()->cascadeOnDelete();
                $table->string('theme_id');
                $table->string('template_id');
                $table->json('value');
                $table->integer('sort')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_template_options');
    }
};
