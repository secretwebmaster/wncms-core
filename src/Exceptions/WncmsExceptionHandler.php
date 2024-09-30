<?php

namespace Wncms\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class WncmsExceptionHandler extends ExceptionHandler
{
    public function render($request, \Throwable $exception)
    {
        // Avoid infinite loop
        if (url()->previous() == url()->current()) {
            return parent::render($request, $exception);
        }

        if ($exception instanceof QueryException) {
            logger()->error($exception);

            if ($exception->getCode() === '42S02') {
                return back()->withErrors([
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        // Ohter exceptions
        // if ($exception instanceof MethodNotAllowedHttpException) {
        //     logger()->error($exception);
        //     return response()->view('errors.405');
        // }

        return parent::render($request, $exception);
    }
}
