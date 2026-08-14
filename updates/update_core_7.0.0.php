<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Database\Schema\ApiAuthSchema;

$thisVersion = '7.0.0';

info("running update_{$thisVersion}.php");

try {
    $upgrade = static function () use ($thisVersion): void {
        ApiAuthSchema::assertCompatibleExistingTables();

        if (! Schema::hasTable('mutation_audits')) {
            Schema::create('mutation_audits', function (Blueprint $table): void {
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

        ApiAuthSchema::createApiSessions();
        ApiAuthSchema::createApiAccessTokens();
        ApiAuthSchema::createApiRefreshTokens();
        ApiAuthSchema::createApiServiceTokens();
        ApiAuthSchema::createApiSecurityEvents();
        ApiAuthSchema::createApiStepUpProofs();
        ApiAuthSchema::createApiActionPlans();
        ApiAuthSchema::assertCompatibleExistingTables();

        $settings = AuthSecurityConfig::defaultSettings();
        $settings['api_legacy_personal_tokens_enabled'] = true;
        $settings['api_legacy_personal_tokens_cutoff_at'] = CarbonImmutable::now('UTC')->addDays(90)->toIso8601String();
        $settingModel = wncms()->getModelClass('setting');

        foreach ($settings as $key => $value) {
            $query = $settingModel::query()->where('key', $key);
            if (Schema::hasColumn('settings', 'group')) {
                $query->where(static fn ($group) => $group->whereNull('group')->orWhere('group', ''));
            }
            if (! $query->exists() && ! uss($key, $value)) {
                throw new RuntimeException("Unable to seed authentication setting: {$key}");
            }
        }

        $persistedSettings = [];
        foreach (array_keys($settings) as $key) {
            $query = $settingModel::query()->where('key', $key);
            if (Schema::hasColumn('settings', 'group')) {
                $query->where(static fn ($group) => $group->whereNull('group')->orWhere('group', ''));
            }
            $persistedSettings[$key] = $query->value('value');
        }
        $settingErrors = AuthSecurityConfig::fromValues($persistedSettings)->validate();
        if ($settingErrors !== []) {
            throw new RuntimeException('Authentication settings failed validation: '.implode(', ', array_keys($settingErrors)));
        }

        $guardName = trim((string) config('auth.defaults.guard')) ?: 'web';
        $permissionNames = [
            'api_token_create', 'api_token_create_cross_site', 'api_token_create_permanent',
            'api_token_index', 'api_token_show', 'api_token_rotate', 'api_token_revoke',
            'security_event_index', 'security_event_show', 'blade_mode_manage',
        ];
        $roles = Role::query()->whereIn('name', ['superadmin', 'admin'])->where('guard_name', $guardName)->get();
        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guardName]);
            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! uss('core_version', $thisVersion)) {
            throw new RuntimeException('Unable to persist the completed core version.');
        }
    };

    if (in_array(DB::connection()->getDriverName(), ['sqlite', 'pgsql'], true)) {
        DB::transaction($upgrade);
    } else {
        $upgrade();
    }

    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: '.$e->getMessage());
    throw $e;
}
