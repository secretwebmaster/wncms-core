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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('status')->default('published'); // published | drafted | trashed 
            $table->string('visibility')->default('public'); // public | member | admin

            $table->string('type')->default('plain'); // plain | builder1 | builder2
            $table->string('blade_name')->nullable();

            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('is_locked')->default(false);

            $table->text('options')->nullable(); // 3.1.5

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
