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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain')->unique();
            $table->string('site_name');
            $table->string('site_logo')->nullable();
            $table->string('site_favicon')->nullable();
            $table->string('site_slogan')->nullable();
            $table->string('site_seo_keywords')->nullable();
            $table->string('site_seo_description')->nullable();

            $table->string('theme')->nullable();
            $table->string('remark')->nullable();

            $table->text('meta_verification')->nullable();
            $table->text('head_code')->nullable();
            $table->text('body_code')->nullable();
            $table->text('analytics')->nullable();


            $table->string('license')->nullable();

            $table->boolean('enabled_page_cache')->default(false);
            $table->boolean('enabled_data_cache')->default(true);

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
        Schema::dropIfExists('websites');
    }
};
