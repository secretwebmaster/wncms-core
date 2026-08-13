<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Wncms\Database\Schema\ApiAuthSchema;

return new class extends Migration
{
    public function up(): void
    {
        ApiAuthSchema::createApiStepUpProofs();
    }

    public function down(): void
    {
        Schema::dropIfExists('api_step_up_proofs');
    }
};
