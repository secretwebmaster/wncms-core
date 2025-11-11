<?php

namespace Wncms\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class WncmsExceptionHandler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // Avoid infinite loop
        if (url()->previous() == url()->current()) {
            return parent::render($request, $exception);
        }

        // Handle database errors
        if ($exception instanceof QueryException) {
            logger()->error($exception);

            if ($exception->getCode() === '42S02') {
                return back()->withErrors([
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        // Handle HTTP exceptions (404, 403, 500, etc.)
        if ($this->isHttpException($exception)) {
            $status = $exception->getStatusCode();
            $theme  = 'default'; // fallback

            try {
                $website = wncms()->website()->get();
                if ($website && !empty($website->theme)) {
                    $theme = $website->theme;
                }
            } catch (\Throwable $e) {
                // Log but don’t crash
                \Log::warning("Failed to fetch theme in ExceptionHandler: " . $e->getMessage());
            }

            // 1. Theme error page
            if (view()->exists("frontend.themes.{$theme}.errors.{$status}")) {
                return response()->view("frontend.themes.{$theme}.errors.{$status}", [], $status);
            }

            // 2. App default error page
            if (view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", [], $status);
            }

            // 3. Package fallback
            if (view()->exists("wncms::errors.{$status}")) {
                return response()->view("wncms::errors.{$status}", [], $status);
            }
        }

        // Default Laravel handling
        return parent::render($request, $exception);
    }
}
