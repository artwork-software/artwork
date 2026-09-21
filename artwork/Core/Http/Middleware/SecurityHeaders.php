<?php

namespace Artwork\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Grundschutz-Header fuer alle Browser-Antworten (Gruppen web, external, external.guest —
 * nicht api). Erzeugt pro Request einen CSP-Nonce, den @vite, @routes und die Inline-Scripts
 * der Blade-Layouts tragen. Sicherheits-Audit 21.09.2026, Befunde D/G.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Muss vor dem Rendern laufen: @vite/@routes lesen den Nonce beim Ausgeben der Tags.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS nur auf https — sonst wuerde ein reiner http-Intranetbetrieb dauerhaft ausgesperrt.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $headers->set(
            config('security.csp_enforce') ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            $this->buildContentSecurityPolicy($nonce)
        );

        return $response;
    }

    private function buildContentSecurityPolicy(string $nonce): string
    {
        $devOrigin = $this->viteDevOrigin();
        $dev = $devOrigin === null ? '' : ' ' . $devOrigin;
        // HMR-WebSocket des Vite-Dev-Servers (https://host:5173 -> wss://host:5173).
        $devWs = $devOrigin === null ? '' : ' ' . preg_replace('#^http#', 'ws', $devOrigin);

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$dev}",
            "style-src 'self' 'unsafe-inline'{$dev}",
            "img-src 'self' data: blob:{$dev}",
            "font-src 'self' data:{$dev}",
            "connect-src 'self' ws: wss:{$dev}{$devWs}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }

    /**
     * Im local-Environment laeuft Vite als Dev-Server auf eigener Origin (public/hot, z. B.
     * https://artwork.ddev.site:5173). Scripts, Styles, Assets und der HMR-WebSocket kommen
     * von dort und muessen in der Policy erlaubt sein.
     */
    private function viteDevOrigin(): ?string
    {
        if (!app()->environment('local') || !Vite::isRunningHot()) {
            return null;
        }

        $origin = rtrim(trim((string) file_get_contents(Vite::hotFile())), '/');

        return preg_match('#^https?://#', $origin) === 1 ? $origin : null;
    }
}
