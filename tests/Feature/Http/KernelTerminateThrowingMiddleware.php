<?php

namespace Tests\Feature\Http;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wirft beim Bau – wie Passports CheckToken, wenn der ResourceServer an der OAuth-Key-Rechteprüfung scheitert.
 * Der Kernel baut Middleware für terminate() neu; der erste Bau im Request wird über $failOnConstruct ausgelassen.
 */
final class KernelTerminateThrowingMiddleware
{
    public static bool $failOnConstruct = false;

    public function __construct()
    {
        if (self::$failOnConstruct) {
            throw new RuntimeException('Key file permissions are not correct');
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        self::$failOnConstruct = true;

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
    }
}
