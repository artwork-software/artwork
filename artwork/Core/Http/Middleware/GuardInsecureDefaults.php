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
 * Sperrt in production jeden Webzugriff (503), solange öffentlich bekannte Secrets konfiguriert sind;
 * artisan bleibt nutzbar, damit sich die .env korrigieren lässt. Weniger kritische Befunde und
 * „APP_ENV ≠ production unter öffentlicher Adresse“ werden höchstens einmal täglich geloggt.
 * Die Antwort nennt bewusst keine Details, die stehen nur im Log.
 */
class GuardInsecureDefaults
{
    public function __construct(private readonly InsecureDefaultsDetector $detector)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $blocking = $this->detector->blockingFindings();
            if ($blocking !== []) {
                $this->logOnce('insecure-defaults.blocking', 3600, $blocking, true);

                return response(
                    'artwork is not ready for production use: insecure default configuration. '
                    . 'The operator finds the details in the server log.',
                    Response::HTTP_SERVICE_UNAVAILABLE,
                    ['Content-Type' => 'text/plain; charset=UTF-8', 'Retry-After' => '3600']
                );
            }

            $warnings = $this->detector->warningFindings();
            if ($warnings !== []) {
                $this->logOnce('insecure-defaults.warning', 86400, $warnings, false);
            }
        } elseif ($this->detector->runsNonProductionOnPublicUrl()) {
            $this->logOnce('insecure-defaults.environment', 86400, [
                'APP_ENV is "' . app()->environment() . '" although APP_URL is a public address. '
                . 'Set APP_ENV=production.',
            ], false);
        }

        return $next($request);
    }

    /**
     * @param list<string> $findings
     */
    private function logOnce(string $key, int $seconds, array $findings, bool $blocking): void
    {
        try {
            if (!Cache::add($key, true, $seconds)) {
                return;
            }
        } catch (Throwable) {
            // Ohne Cache lieber zu oft loggen als gar nicht.
        }

        Log::critical(
            $blocking
                ? 'artwork blocks all web requests because of insecure default configuration'
                : 'artwork runs with insecure default configuration',
            ['findings' => $findings, 'docs' => 'README.md, section "Produktivbetrieb"']
        );
    }
}
