<?php

namespace Tests\Feature\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class KernelTerminateRecordingMiddleware
{
    public static int $terminated = 0;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        self::$terminated++;
    }
}
