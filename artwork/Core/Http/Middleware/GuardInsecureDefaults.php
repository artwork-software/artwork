<?php

namespace Artwork\Core\Http\Middleware;

use Artwork\Core\Services\InsecureDefaultsDetector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Meldet öffentlich bekannte Secrets und „APP_ENV ≠ production unter öffentlicher Adresse“ höchstens
 * einmal täglich als critical-Log. Der Request läuft immer normal weiter (Entscheidung 20.10.2026:
 * keine Sperre, damit keine Selbst-Hoster-Instanz abgeschaltet wird). Ohne Fund kein Cache-Zugriff.
 */
class GuardInsecureDefaults
{
    private const CACHE_KEY = 'insecure-defaults.logged';

    private const LOG_INTERVAL_SECONDS = 86400;

    /** Prozess-lokale Drossel für langlebige Worker (Swoole), spart dort den Cache-Zugriff. */
    private static int $nextLogAttemptAt = 0;

    public function __construct(private readonly InsecureDefaultsDetector $detector)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (time() >= self::$nextLogAttemptAt) {
            $findings = $this->findings();
            if ($findings !== []) {
                $this->logOncePerDay($findings);
            }
        }

        return $next($request);
    }

    /**
     * Setzt die prozess-lokale Drossel zurück (Tests).
     */
    public static function resetThrottle(): void
    {
        self::$nextLogAttemptAt = 0;
    }

    /**
     * @return list<string>
     */
    private function findings(): array
    {
        if (app()->environment('production')) {
            return $this->detector->productionFindings();
        }

        if ($this->detector->runsNonProductionOnPublicUrl()) {
            return [
                'APP_ENV is "' . app()->environment() . '" although APP_URL is a public address. '
                . 'Set APP_ENV=production.',
            ];
        }

        return [];
    }

    /**
     * @param list<string> $findings
     */
    private function logOncePerDay(array $findings): void
    {
        self::$nextLogAttemptAt = time() + self::LOG_INTERVAL_SECONDS;

        try {
            if (!Cache::add(self::CACHE_KEY, true, self::LOG_INTERVAL_SECONDS)) {
                return;
            }
        } catch (Throwable) {
            // Ohne Cache lieber zu oft loggen als gar nicht.
        }

        Log::critical(
            'artwork runs with insecure default configuration',
            ['findings' => $findings, 'docs' => 'README.md, section "Produktivbetrieb"']
        );
    }
}
