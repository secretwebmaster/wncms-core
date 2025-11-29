<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('page_builder_contents')) {
            Schema::create('page_builder_contents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('page_id')->index();
                $table->string('builder_type')->default('default');
                $table->unsignedInteger('version')->default(1);
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_builder_contents');
    }
};
