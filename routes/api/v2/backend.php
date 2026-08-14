<?php

use Illuminate\Support\Facades\Route;
use Wncms\Api\V2\LegacyOperationSecurity;
use Wncms\Http\Controllers\Api\V2\Backend\ActionPlanController;
use Wncms\Http\Controllers\Api\V2\Backend\AuthController;
use Wncms\Http\Controllers\Api\V2\Backend\BridgeController;
use Wncms\Http\Controllers\Api\V2\Backend\BladeSecurityController;
use Wncms\Http\Controllers\Api\V2\Backend\I18nController;
use Wncms\Http\Controllers\Api\V2\Backend\OperationController;
use Wncms\Http\Controllers\Api\V2\Backend\ProfileSecurityController;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;
use Wncms\Http\Controllers\Api\V2\Backend\SessionController;
use Wncms\Http\Controllers\Api\V2\Backend\ServiceTokenController;
use Wncms\Http\Controllers\Api\V2\Backend\SecurityEventController;

Route::prefix('v2/backend')
    ->name('api.v2.backend.')
    ->middleware(['api', 'api_v2_whitelist'])
    ->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])
            ->middleware(['api_v2_refresh_transport', 'api_v2_refresh_origin', 'throttle:api-v2-login'])
            ->name('auth.login');
        Route::post('/auth/refresh', [AuthController::class, 'refresh'])
            ->middleware(['api_v2_refresh_transport', 'api_v2_refresh_origin', 'api_v2_refresh_csrf'])
            ->name('auth.refresh');
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware(['api_v2_refresh_transport', 'api_v2_refresh_origin', 'api_v2_refresh_csrf'])
            ->name('auth.logout');
        Route::post('/auth/password/forgot', [ProfileSecurityController::class, 'forgotPassword'])
            ->name('auth.password.forgot');
        Route::post('/auth/password/reset', [ProfileSecurityController::class, 'resetPassword'])
            ->name('auth.password.reset');
        Route::post('/auth/email-verification/verify', [ProfileSecurityController::class, 'confirmEmailVerification'])
            ->name('auth.email_verification.verify');
        Route::post('/auth/email/change/confirm', [ProfileSecurityController::class, 'confirmEmailChange'])
            ->name('auth.email.change.confirm');

        Route::middleware(['api_v2_token_auth', 'api_v2_legacy_headers'])->group(function () {
            Route::post('/auth/reauthenticate', [AuthController::class, 'reauthenticate'])
                ->middleware('throttle:api-v2-reauthenticate')
                ->name('auth.reauthenticate');
            Route::post('/action-plans', [ActionPlanController::class, 'store'])
                ->name('action_plans.store');
            Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])
                ->defaults('api_operation_id', 'backend.auth.logout_all')
                ->defaults('api_website_identity', 'global:interactive-sessions')
                ->middleware('api_v2_idempotency')
                ->name('auth.logout_all');
            Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::get('/security/blade', [BladeSecurityController::class, 'show'])
                ->defaults('api_operation_id', 'backend.security.blade.show')
                ->middleware(['api_v2_ability:security.blade', 'api_v2_permission:blade_mode_manage'])
                ->name('security.blade.show');
            Route::patch('/security/blade', [BladeSecurityController::class, 'update'])
                ->defaults('api_operation_id', 'backend.security.blade.update')
                ->defaults('api_website_identity', 'global:blade-availability')
                ->defaults('api_sensitive_idempotency', true)
                ->middleware(['api_v2_ability:security.blade', 'api_v2_permission:blade_mode_manage', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('security.blade.update');
            Route::get('/security/events', [SecurityEventController::class, 'index'])
                ->defaults('api_operation_id', 'backend.security.events.index')
                ->middleware(['api_v2_ability:security.events', 'api_v2_permission:security_event_index'])
                ->name('security.events.index');
            Route::get('/security/events/{event_id}', [SecurityEventController::class, 'show'])
                ->defaults('api_operation_id', 'backend.security.events.show')
                ->middleware(['api_v2_ability:security.events', 'api_v2_permission:security_event_show'])
                ->name('security.events.show');
            Route::patch('/auth/profile', [ProfileSecurityController::class, 'updateProfile'])
                ->defaults('api_operation_id', 'backend.auth.profile.update')
                ->middleware('api_v2_ability:account.profile')
                ->name('auth.profile.update');
            Route::patch('/auth/password', [ProfileSecurityController::class, 'changePassword'])
                ->defaults('api_operation_id', 'backend.auth.password.update')
                ->defaults('api_website_identity', 'global:account-security')
                ->middleware(['api_v2_ability:account.password', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('auth.password.update');
            Route::post('/auth/email/change', [ProfileSecurityController::class, 'requestEmailChange'])
                ->defaults('api_operation_id', 'backend.auth.email.change')
                ->defaults('api_website_identity', 'global:account-security')
                ->middleware(['api_v2_ability:account.email', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('auth.email.change');
            Route::post('/auth/email-verification/send', [ProfileSecurityController::class, 'sendEmailVerification'])
                ->defaults('api_operation_id', 'backend.auth.email_verification.send')
                ->middleware('api_v2_ability:account.email')
                ->name('auth.email_verification.send');
            Route::get('/auth/sessions', [SessionController::class, 'index'])->name('auth.sessions.index');
            Route::delete('/auth/sessions/{session_id}', [SessionController::class, 'destroy'])
                ->defaults('api_operation_id', 'backend.auth.sessions.destroy')
                ->defaults('api_website_identity', 'global:interactive-sessions')
                ->middleware('api_v2_idempotency')
                ->name('auth.sessions.destroy');
            Route::get('/auth/service-token-options', [ServiceTokenController::class, 'options'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.options')
                ->middleware(['api_v2_ability:tokens.create', 'api_v2_permission:api_token_create'])
                ->name('auth.service_tokens.options');
            Route::get('/auth/service-tokens', [ServiceTokenController::class, 'index'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.index')
                ->middleware(['api_v2_ability:tokens.read', 'api_v2_permission:api_token_index'])
                ->name('auth.service_tokens.index');
            Route::post('/auth/service-tokens', [ServiceTokenController::class, 'store'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.store')
                ->defaults('api_website_identity', 'global:service-tokens')
                ->defaults('api_sensitive_idempotency', true)
                ->defaults('api_idempotency_ttl_seconds', 300)
                ->middleware(['api_v2_ability:tokens.create', 'api_v2_permission:api_token_create', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('auth.service_tokens.store');
            Route::get('/auth/service-tokens/{token_id}', [ServiceTokenController::class, 'show'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.show')
                ->middleware(['api_v2_ability:tokens.read', 'api_v2_permission:api_token_show'])
                ->name('auth.service_tokens.show');
            Route::post('/auth/service-tokens/{token_id}/rotate', [ServiceTokenController::class, 'rotate'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.rotate')
                ->defaults('api_website_identity', 'global:service-tokens')
                ->defaults('api_sensitive_idempotency', true)
                ->defaults('api_idempotency_ttl_seconds', 300)
                ->middleware(['api_v2_ability:tokens.rotate', 'api_v2_permission:api_token_rotate', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('auth.service_tokens.rotate');
            Route::delete('/auth/service-tokens/{token_id}', [ServiceTokenController::class, 'destroy'])
                ->defaults('api_operation_id', 'backend.auth.service_tokens.destroy')
                ->defaults('api_website_identity', 'global:service-tokens')
                ->middleware(['api_v2_ability:tokens.revoke', 'api_v2_permission:api_token_revoke', 'api_v2_risk_context', 'api_v2_idempotency', 'api_v2_risk'])
                ->name('auth.service_tokens.destroy');
            Route::get('/i18n/ui', [I18nController::class, 'ui'])->name('i18n.ui');
            Route::get('/translations', [I18nController::class, 'translations'])->name('translations');
            Route::get('/operations/{id}', [OperationController::class, 'show'])->name('operations.show');
            Route::post('/operations/{id}/cancel', [OperationController::class, 'cancel'])
                ->defaults('api_operation_id', 'backend.operations.cancel')
                ->middleware('api_v2_idempotency')
                ->name('operations.cancel');
        });

        Route::middleware(['api_v2_token_auth', 'api_v2_legacy_headers'])->group(function () {
            foreach (config('wncms-backend-api-v2.resources', []) as $resource => $resourceConfig) {
                $enabledActions = $resourceConfig['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
                $controller = $resourceConfig['controller'] ?? ResourceController::class;

                if (in_array('index', $enabledActions, true)) {
                    Route::get("/{$resource}", [$controller, 'index'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.index")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'index', $resourceConfig))
                        ->name("{$resource}.index");
                }

                if (in_array('show', $enabledActions, true)) {
                    Route::get("/{$resource}/{id}", [$controller, 'show'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.show")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'show', $resourceConfig))
                        ->name("{$resource}.show");
                }

                if (in_array('store', $enabledActions, true)) {
                    Route::post("/{$resource}", [$controller, 'store'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.store")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'store', $resourceConfig))
                        ->name("{$resource}.store");
                }

                if (in_array('update', $enabledActions, true)) {
                    Route::patch("/{$resource}/{id}", [$controller, 'update'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.update")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'update', $resourceConfig))
                        ->name("{$resource}.update");
                }

                if (in_array('destroy', $enabledActions, true)) {
                    Route::delete("/{$resource}/{id}", [$controller, 'destroy'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.destroy")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'destroy', $resourceConfig))
                        ->name("{$resource}.destroy");
                }

                if (($resourceConfig['enable_bulk_delete'] ?? true) === true && in_array('bulk_delete', $enabledActions, true)) {
                    Route::post("/{$resource}/bulk_delete", [$controller, 'bulkDelete'])
                        ->defaults('resource', $resource)
                        ->defaults('api_operation_id', "backend.{$resource}.bulk_delete")
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'bulk_delete', $resourceConfig))
                        ->name("{$resource}.bulk_delete");
                }
            }

            foreach (config('wncms-backend-api-v2.actions', []) as $action) {
                $method = strtolower((string) ($action['method'] ?? 'post'));
                $uri = (string) ($action['uri'] ?? '');
                $name = (string) ($action['name'] ?? '');
                if ($uri === '' || $name === '') {
                    continue;
                }

                Route::match([$method], "/{$uri}", [BridgeController::class, 'dispatch'])
                    ->defaults('name', $name)
                    ->defaults('api_operation_id', "backend.{$name}")
                    ->middleware(LegacyOperationSecurity::actionMiddleware($action))
                    ->name($name);
            }
        });
    });
