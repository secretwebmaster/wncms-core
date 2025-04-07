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
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->morphs('commentable');
                $table->string('status')->default('visible'); // approved | pending | rejected
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
                $table->string('content');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
