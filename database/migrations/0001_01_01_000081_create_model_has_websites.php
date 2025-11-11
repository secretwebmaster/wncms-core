<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('model_has_websites')) {
            Schema::create('model_has_websites', function (Blueprint $table) {
                $table->foreignId('website_id')->constrained()->cascadeOnDelete();
                $table->morphs('model');
                $table->primary(['website_id', 'model_id', 'model_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_has_websites');
    }
};
