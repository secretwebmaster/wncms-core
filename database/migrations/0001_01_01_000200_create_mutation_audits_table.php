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
        if (!Schema::hasTable('mutation_audits')) {
            Schema::create('mutation_audits', function (Blueprint $table) {
                $table->id();
                $table->string('run_id')->nullable()->index();
                $table->string('surface')->index();
                $table->string('actor_type')->nullable()->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('domain')->index();
                $table->string('action')->index();
                $table->string('model_key')->nullable()->index();
                $table->string('model_type')->nullable()->index();
                $table->unsignedBigInteger('model_id')->nullable()->index();
                $table->json('website_ids')->nullable();
                $table->string('permission')->nullable()->index();
                $table->json('input_summary')->nullable();
                $table->unsignedSmallInteger('result_code')->nullable()->index();
                $table->string('result_status')->nullable()->index();
                $table->text('message')->nullable();
                $table->json('error_summary')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['domain', 'action']);
                $table->index(['surface', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutation_audits');
    }
};
