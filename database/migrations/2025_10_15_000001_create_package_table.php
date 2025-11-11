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
        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('package_id')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('url')->nullable();
                $table->string('author')->nullable();
                $table->string('version')->default('1.0.0');
                $table->string('status')->default('inactive');
                $table->string('path'); // absolute base path (e.g. vendor/secretwebmaster/wncms-faqs)
                $table->string('remark')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
