<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order_column')->nullable();
            $table->timestamps();

            // add indexes
            $table->unique(['type','slug']);
            $table->index(['type', 'name']);
            $table->index('order_column');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};