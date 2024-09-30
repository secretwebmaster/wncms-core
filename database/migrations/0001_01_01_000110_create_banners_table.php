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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active'); // active | paused | pending | suspended
            $table->string('external_thumbnail')->nullable();
            $table->string('url')->nullable();
            $table->integer('order')->nullable();
            $table->string('contact')->nullable();
            $table->string('remark')->nullable();
            $table->json('positions')->nullable(); // header|above_post|custom_position_1|....
            $table->datetime('expired_at')->nullable();
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
        Schema::dropIfExists('banners');
    }
};
