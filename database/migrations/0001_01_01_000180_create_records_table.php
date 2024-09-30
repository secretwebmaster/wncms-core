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
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // modele | post, page, trasaction, etc
            $table->string('sub_type')->nullable(); //model function name | create, puchase, store, etc
            $table->string('status')->nullable(); // success | fail
            $table->text('message'); // one sentence
            $table->text('detail')->nullable(); // long sentence
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
};
