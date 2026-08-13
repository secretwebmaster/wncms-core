<?php

use Illuminate\Support\Facades\Route;
use Wncms\Api\V2\LegacyOperationSecurity;
use Wncms\Http\Controllers\Api\V2\Backend\AuthController;
use Wncms\Http\Controllers\Api\V2\Backend\BridgeController;
use Wncms\Http\Controllers\Api\V2\Backend\I18nController;
use Wncms\Http\Controllers\Api\V2\Backend\OperationController;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;

Route::prefix('v2/backend')
    ->name('api.v2.backend.')
    ->middleware(['api', 'api_v2_whitelist'])
    ->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware(['api_v2_token_auth'])->group(function () {
            Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::get('/i18n/ui', [I18nController::class, 'ui'])->name('i18n.ui');
            Route::get('/translations', [I18nController::class, 'translations'])->name('translations');
            Route::get('/operations/{id}', [OperationController::class, 'show'])->name('operations.show');
            Route::post('/operations/{id}/cancel', [OperationController::class, 'cancel'])
                ->defaults('api_operation_id', 'backend.operations.cancel')
                ->middleware('api_v2_idempotency')
                ->name('operations.cancel');
        });

        Route::middleware(['api_v2_token_auth'])->group(function () {
            foreach (config('wncms-backend-api-v2.resources', []) as $resource => $resourceConfig) {
                $enabledActions = $resourceConfig['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
                $controller = $resourceConfig['controller'] ?? ResourceController::class;

                if (in_array('index', $enabledActions, true)) {
                    Route::get("/{$resource}", [$controller, 'index'])
                        ->defaults('resource', $resource)
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'index', $resourceConfig))
                        ->name("{$resource}.index");
                }

                if (in_array('show', $enabledActions, true)) {
                    Route::get("/{$resource}/{id}", [$controller, 'show'])
                        ->defaults('resource', $resource)
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'show', $resourceConfig))
                        ->name("{$resource}.show");
                }

                if (in_array('store', $enabledActions, true)) {
                    Route::post("/{$resource}", [$controller, 'store'])
                        ->defaults('resource', $resource)
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'store', $resourceConfig))
                        ->name("{$resource}.store");
                }

                if (in_array('update', $enabledActions, true)) {
                    Route::patch("/{$resource}/{id}", [$controller, 'update'])
                        ->defaults('resource', $resource)
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'update', $resourceConfig))
                        ->name("{$resource}.update");
                }

                if (in_array('destroy', $enabledActions, true)) {
                    Route::delete("/{$resource}/{id}", [$controller, 'destroy'])
                        ->defaults('resource', $resource)
                        ->middleware(LegacyOperationSecurity::resourceMiddleware($resource, 'destroy', $resourceConfig))
                        ->name("{$resource}.destroy");
                }

                if (($resourceConfig['enable_bulk_delete'] ?? true) === true && in_array('bulk_delete', $enabledActions, true)) {
                    Route::post("/{$resource}/bulk_delete", [$controller, 'bulkDelete'])
                        ->defaults('resource', $resource)
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
                    ->middleware(LegacyOperationSecurity::actionMiddleware($action))
                    ->name($name);
            }
        });
    });
