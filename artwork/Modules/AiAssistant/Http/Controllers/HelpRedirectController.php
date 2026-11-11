<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /help/{pfad}` leitet auf das artwork-Wiki weiter (core-help-redirect).
 *
 * Grund: Die KI-Bar macht nur **relative** Ziele zu Links (Konzept C4 §4), damit artwork-ai nicht
 * auf fremde Seiten verlinken kann. Antworten aus der Dokumentation (S5 §6) brauchen trotzdem einen
 * Weg ins Wiki — den stellt diese Weiterleitung bereit.
 *
 * Kein offener Redirect: die Zieldomain steht fest in `config('ai.wiki_base_url')`, angehängt wird
 * nur der Pfad, und der ist ueber die Route auf Buchstaben, Ziffern, `_ - / #` begrenzt. Ein Punkt
 * ist nicht erlaubt, `..` kommt also gar nicht erst an.
 */
class HelpRedirectController extends Controller
{
    /** Sprachsegment der Wiki-Basis (`/de/docs`), das nach der Sprache des Benutzers gesetzt wird. */
    private const LOCALE_SEGMENT_PATTERN = '#/(de|en)(/|$)#';

    private const SUPPORTED_LOCALES = ['de', 'en'];

    public function __invoke(Request $request, string $path = ''): RedirectResponse
    {
        $base = $this->localizedBase((string) config('ai.wiki_base_url'), $request);
        $target = rtrim($base, '/');
        $path = ltrim($path, '/');

        if ($path !== '') {
            $target .= '/' . $path;
        }

        // away(), weil das Ziel ausserhalb von artwork liegt; ohne Cache, damit ein Wechsel der
        // Sprache oder der Konfiguration sofort greift.
        return redirect()->away($target, Response::HTTP_FOUND)
            ->header('Cache-Control', 'no-store, private');
    }

    /** Ersetzt das Sprachsegment der Basis durch die Sprache des Benutzers. */
    private function localizedBase(string $base, Request $request): string
    {
        $locale = (string) ($request->user()?->language ?? app()->getLocale());

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            return $base;
        }

        return preg_replace(self::LOCALE_SEGMENT_PATTERN, '/' . $locale . '$2', $base, 1) ?? $base;
    }
}
