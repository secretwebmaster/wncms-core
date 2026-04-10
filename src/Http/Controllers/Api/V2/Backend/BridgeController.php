<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BridgeController extends ApiV2Controller
{
    public function dispatch(Request $request)
    {
        try {
            $name = (string) $request->route('name', '');
            $action = collect(config('wncms-backend-api-v2.actions', []))
                ->first(fn(array $item) => ($item['name'] ?? null) === $name);

            if (!$action) {
                return $this->error('action_not_supported', SymfonyResponse::HTTP_NOT_FOUND);
            }

            if (!empty($action['permission'])) {
                abort_unless(auth()->user()?->can($action['permission']), SymfonyResponse::HTTP_FORBIDDEN);
            }

            $controller = app($action['controller']);
            $method = $action['action'];

            $response = app()->call([$controller, $method], $request->route()?->parameters() + [
                'request' => $request,
            ]);

            return $this->normalizeBridgeResponse($response);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    protected function normalizeBridgeResponse(mixed $response): JsonResponse
    {
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);

            if (is_array($payload) && isset($payload['code'], $payload['status'], $payload['message'])) {
                if (!array_key_exists('meta', $payload)) {
                    $payload['meta'] = [];
                }
                if (!array_key_exists('errors', $payload)) {
                    $payload['errors'] = [];
                }

                return response()->json($payload, $response->getStatusCode());
            }

            return $this->ok($payload ?? null);
        }

        if ($response instanceof RedirectResponse) {
            return $this->ok([
                'redirect' => $response->getTargetUrl(),
            ], 'successfully_processed');
        }

        if ($response instanceof Response) {
            $content = $response->getContent();
            $decoded = json_decode((string) $content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->ok($decoded);
            }

            return $this->ok([
                'content' => $content,
            ], 'successfully_processed');
        }

        if ($response instanceof SymfonyResponse) {
            return $this->ok([
                'content' => $response->getContent(),
            ], 'successfully_processed');
        }

        if (is_array($response)) {
            return $this->ok($response);
        }

        return $this->ok($response);
    }
}
