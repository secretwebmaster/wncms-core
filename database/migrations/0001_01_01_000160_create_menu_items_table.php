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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('model_type')->nullable(); // tag | post | video | null = external link
            $table->string('model_id')->nullable(); // id | slug | null
            $table->string('icon')->nullable(); // fontawsome class
            $table->string('type')->nullable(); // post_category | video_tag | external_link
            $table->string('name')->nullable(); // override default model name
            $table->string('url')->nullable(); // auto assign to model route when null for model | auto javascript:; when null for external link | link 
            $table->boolean('is_new_window')->default(false);
            $table->boolean('is_mega_menu')->default(false);
            $table->integer('order')->default(0);

            $table->string('description')->nullable(); //3.1.5

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
