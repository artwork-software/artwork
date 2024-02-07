<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                if(env('APP_ENV', 'local') === 'local' || app()->runningUnitTests()) {
                    return;
                }
                app('sentry')->captureException($e);
            }
        });
    }
}
