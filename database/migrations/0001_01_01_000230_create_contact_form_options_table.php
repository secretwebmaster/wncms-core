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
        Schema::create('contact_form_options', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // field name 
            $table->string('type'); // text | textarea | select
            $table->string('display_name')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('default_value')->nullable();
            $table->string('options')->nullable(); // comma separated
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_form_options');
    }
};
