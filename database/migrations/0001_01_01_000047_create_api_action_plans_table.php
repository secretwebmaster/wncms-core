<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Wncms\Database\Schema\ApiAuthSchema;

return new class extends Migration
{
    public function up(): void
    {
        ApiAuthSchema::createApiActionPlans();
    }

    public function down(): void
    {
        Schema::dropIfExists('api_action_plans');
    }
};
