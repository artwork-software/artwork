<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * terminate() der Middleware läuft nach dem Senden der Antwort. Eine Exception dort endete auf Prod als
 * "headers already sent"-Fatal (Sentry 20.10.2026: OAuth-Key mit Rechten 644, Passports CheckToken scheitert
 * beim Neubau für terminate()). Der Kernel meldet solche Fehler jetzt und beendet die übrigen Middleware trotzdem.
 */
final class KernelTerminateMiddlewareTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        KernelTerminateThrowingMiddleware::$failOnConstruct = false;
        KernelTerminateRecordingMiddleware::$terminated = 0;

        Route::middleware([KernelTerminateThrowingMiddleware::class, KernelTerminateRecordingMiddleware::class])
            ->get('/__test/kernel-terminate', static fn (): string => 'ok');
    }

    #[Test]
    public function a_failing_middleware_in_terminate_is_reported_and_the_others_still_terminate(): void
    {
        $exceptions = Exceptions::fake();

        // Der Test-Kernel ruft terminate() wie FPM nach dem Senden auf; ohne Absicherung flöge die Exception hier.
        $this->get('/__test/kernel-terminate')->assertOk()->assertSee('ok');

        $this->assertSame(1, KernelTerminateRecordingMiddleware::$terminated);
        $exceptions->assertReported(
            static fn (RuntimeException $exception): bool
                => $exception->getMessage() === 'Key file permissions are not correct'
        );
    }

    #[Test]
    public function terminable_middleware_runs_normally_without_failures(): void
    {
        $exceptions = Exceptions::fake();

        Route::middleware([KernelTerminateRecordingMiddleware::class])
            ->get('/__test/kernel-terminate-clean', static fn (): string => 'ok');

        $this->get('/__test/kernel-terminate-clean')->assertOk();

        $this->assertSame(1, KernelTerminateRecordingMiddleware::$terminated);
        $exceptions->assertNothingReported();
    }
}
