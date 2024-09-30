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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('status')->default('published'); // published | drafted | trashed 
            $table->string('visibility')->default('public'); // public | member | admin

            $table->string('external_thumbnail')->nullable();
            $table->string('slug')->unique();

            $table->string('title');
            $table->string('label')->nullable();
            $table->string('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->string('remark')->nullable();
            $table->integer('order')->nullable();

            $table->string('password')->nullable();
            $table->decimal('price', 9, 3)->nullable();

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_dmca')->default(false);

            $table->dateTime('published_at');
            $table->dateTime('expired_at')->nullable();

            $table->string('source')->nullable();
            $table->string('ref_id')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
