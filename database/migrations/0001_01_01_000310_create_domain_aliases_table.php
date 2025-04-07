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
        if (!Schema::hasTable('domain_aliases')) {
            Schema::create('domain_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('website_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('domain');
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
        Schema::dropIfExists('domain_aliases');
    }
};
