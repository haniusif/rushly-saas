<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    // public function register()
    // {

    // }

    public function report(Throwable $e)
    {
        parent::report($e);
    }


    public function render($request, Throwable $e)
    {
        if ($this->isHttpException($e)) {
            $code    = $e->getStatusCode();
            // Only 401/403/404/405/419/429/500 have branded Blade templates;
            // anything else (400, 502, 503, …) falls through to Laravel's
            // default renderer, which still emits the correct status code.
            $handled = [401, 403, 404, 405, 419, 429, 500];

            if (in_array($code, $handled, true)) {
                // IMPORTANT: response()->view() defaults to HTTP 200 unless
                // the status code is passed explicitly. Preserving the real
                // status matters for SEO (404s must be 404) and for monitors.
                return response()->view("errors.$code", [
                    // Preserve any headers Symfony attached to the underlying
                    // HttpException so `Allow`, `Retry-After`, etc. survive.
                ], $code, method_exists($e, 'getHeaders') ? $e->getHeaders() : []);
            }
        }

        return parent::render($request, $e);
    }
}
