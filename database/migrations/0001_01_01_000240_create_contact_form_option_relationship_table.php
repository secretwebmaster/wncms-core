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
        Schema::create('contact_form_option_relationship', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->foreign('form_id')->references('id')->on('contact_forms')->cascadeOnDelete();
            $table->unsignedBigInteger('option_id');
            $table->foreign('option_id')->references('id')->on('contact_form_options')->cascadeOnDelete();

            $table->integer('order'); // 3.1.10
            $table->boolean('is_required'); // 3.1.10

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_form_option_relationship');
    }
};
