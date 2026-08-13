<?php

namespace Wncms\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\LoginThrottleService;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Services\Security\SecurityEventService;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    public const DASHBOARD = '/panel/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'Wncms\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->group(__DIR__.'/../../routes/api.php');

            Route::middleware('web')
                ->group(__DIR__.'/../../routes/web.php');
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('api-v2-login', function (Request $request) {
            $window = max(1, (int) config('wncms.auth_security.login_window_minutes', 15));
            $response = function (Request $request, array $headers): Response {
                try {
                    app(SecurityEventService::class)->recordAggregate('auth.login.throttled', 'warning', 'denied', [
                        'surface' => 'api_v2',
                        'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                        'ip' => (string) $request->ip(),
                        'login_identifier' => mb_strtolower(trim((string) $request->input('email'))),
                        'user_agent' => (string) ($request->userAgent() ?: 'unknown-client'),
                        'error_code' => 'authentication.rate_limited',
                        'http_status' => 429,
                        'context' => ['reason' => 'login_throttled'],
                    ]);
                } catch (\Throwable $exception) {
                    Log::warning('WNCMS login throttle event could not be persisted.', [
                        'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                        'exception' => $exception::class,
                    ]);
                }

                $response = app(ApiResponseFactory::class)->failure(
                    'authentication.rate_limited',
                    'Too many login attempts',
                    Response::HTTP_TOO_MANY_REQUESTS,
                );
                foreach ($headers as $name => $value) {
                    $response->headers->set((string) $name, $value);
                }

                return $response;
            };
            $failed = static fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED;

            return [
                Limit::perMinutes($window, max(1, (int) config('wncms.auth_security.login_account_attempts', 5)))
                    ->by(LoginThrottleService::accountKey((string) $request->input('email')))
                    ->after($failed)
                    ->response($response),
                Limit::perMinutes($window, max(1, (int) config('wncms.auth_security.login_ip_attempts', 30)))
                    ->by(LoginThrottleService::ipKey((string) $request->ip()))
                    ->after($failed)
                    ->response($response),
            ];
        });

        RateLimiter::for('api-v2-reauthenticate', function (Request $request) {
            $window = max(1, (int) config('wncms.auth_security.login_window_minutes', 15));
            $failed = static fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED;
            $response = static function (Request $request, array $headers): Response {
                $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
                try {
                    app(SecurityEventService::class)->recordAggregate('auth.step_up.failed', 'warning', 'denied', [
                        'surface' => 'api_v2',
                        'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                        'actor_type' => $context instanceof AuthenticationContext ? $context->actor()::class : null,
                        'actor_id' => $context instanceof AuthenticationContext ? $context->actorId() : null,
                        'credential_type' => $context instanceof AuthenticationContext ? $context->credentialType() : null,
                        'credential_id' => $context instanceof AuthenticationContext ? $context->credentialPublicId() : null,
                        'session_id' => $context instanceof AuthenticationContext ? $context->sessionPublicId() : null,
                        'website_ids' => $context instanceof AuthenticationContext ? $context->websiteIds() : [],
                        'ip' => (string) $request->ip(),
                        'login_identifier' => 'user:'.(string) ($context instanceof AuthenticationContext ? $context->actorId() : 'unknown'),
                        'user_agent' => (string) ($request->userAgent() ?: 'unknown-client'),
                        'error_code' => 'authentication.rate_limited',
                        'http_status' => 429,
                        'context' => ['reason' => 'reauthentication_throttled_account_or_ip'],
                    ]);
                } catch (\Throwable $exception) {
                    report($exception);

                    return app(ApiResponseFactory::class)->failure(
                        'security.audit_unavailable',
                        'Security audit is unavailable',
                        Response::HTTP_SERVICE_UNAVAILABLE,
                    );
                }

                return app(ApiResponseFactory::class)
                    ->failure('authentication.rate_limited', 'Too many reauthentication attempts', Response::HTTP_TOO_MANY_REQUESTS)
                    ->withHeaders($headers);
            };
            $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
            $actorId = (string) ($context instanceof AuthenticationContext ? $context->actorId() : 'unknown');

            return [
                Limit::perMinutes($window, max(1, (int) config('wncms.auth_security.login_account_attempts', 5)))
                    ->by(LoginThrottleService::accountKey('user:'.$actorId))->after($failed)->response($response),
                Limit::perMinutes($window, max(1, (int) config('wncms.auth_security.login_ip_attempts', 30)))
                    ->by(LoginThrottleService::ipKey((string) $request->ip()))->after($failed)->response($response),
            ];
        });
    }
}
