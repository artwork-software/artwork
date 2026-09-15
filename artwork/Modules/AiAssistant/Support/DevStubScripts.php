<?php

namespace Artwork\Modules\AiAssistant\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Feste Event-Skripte des Entwicklungs-Stubs (core-ki-bar-ui, Konzept C4 §9, 0.3 §4–5).
 *
 * Ein Skript ist eine Liste von Events in Sendereihenfolge; jedes Event traegt den SSE-Namen, die
 * Payload und die Wartezeit davor. Die Payloads muessen den Schemata unter
 * contracts/sse-events/ entsprechen — der Stub validiert vor dem Senden, der Feature-Test
 * AiDevStubScriptsTest prueft alle Skripte auf einmal.
 *
 * Ausgewaehlt wird ueber das erste Wort der Nachricht; unbekannte Woerter laufen auf `ok`.
 */
final class DevStubScripts
{
    public const DEFAULT_SCRIPT = 'ok';

    /**
     * Antworttexte in Deltas. `ok` enthaelt Markdown mit Liste und relativem Link, `absolute`
     * einen absoluten Link (muss als Text erscheinen), `html` rohes HTML (muss escaped erscheinen).
     *
     * @var array<string, list<string>>
     */
    private const TEXT_SCRIPTS = [
        'ok' => [
            "## Naechste Schritte\n\n",
            'Ich habe drei ',
            "Punkte gefunden:\n\n",
            "- Probe am Montag\n",
            '- Dienstplan pruefen: ',
            "[zum Dienstplan](/shifts/plan)\n",
            "- Material bestellen\n\n",
            'Mehr steht im **Projekt**.',
        ],
        'absolute' => [
            "Externe Quelle: [artwork.art](https://artwork.art) — ",
            'dieser Link darf kein Link werden. ',
            'Auch protokollrelativ nicht: [evil](//evil.example/steal). ',
            'Intern ist erlaubt: [Projekte](/projects).',
        ],
        'html' => [
            "Rohes HTML im Antworttext:\n\n",
            '<script>alert("xss")</script> ',
            '<b>fett</b> <img src=x onerror=alert(1)> ',
            'muss als Text erscheinen.',
        ],
    ];

    /**
     * Fehler-Skripte: Code, `retryable`, optionale Wartezeit (0.3 §5).
     *
     * @var array<string, array{0: string, 1: bool, 2: int|null, 3: string}>
     */
    private const ERROR_SCRIPTS = [
        'expired' => ['token_expired', true, null, 'Das Session-Token ist abgelaufen.'],
        'rate' => ['rate_limited', true, 30, 'Zu viele Anfragen. Bitte kurz warten.'],
        'budget' => ['budget_exceeded', false, null, 'Das Monatsbudget des Hauses ist erschoepft.'],
        'disabled' => ['tenant_disabled', false, null, 'Der Assistent ist fuer diese Instanz abgeschaltet.'],
        'provider' => ['provider_error', true, null, 'Der Sprachmodell-Anbieter antwortet nicht.'],
        'transcript' => ['transcript_failed', false, null, 'Der Gespraechsverlauf konnte nicht gesichert werden.'],
        'internal' => ['internal', false, null, 'Unerwarteter Fehler.'],
    ];

    /** @return list<string> */
    public static function keywords(): array
    {
        return array_values(array_unique(array_merge(
            array_keys(self::TEXT_SCRIPTS),
            array_keys(self::ERROR_SCRIPTS),
            ['tools', 'slow', 'confirm'],
        )));
    }

    /** Erstes Wort der Nachricht; unbekannt oder leer laeuft auf das Standard-Skript. */
    public static function resolveKeyword(?string $message): string
    {
        $first = strtolower(strtok(trim((string) $message), " \t\n\r") ?: '');

        return in_array($first, self::keywords(), true) ? $first : self::DEFAULT_SCRIPT;
    }

    /**
     * @return list<array{event: string, data: array<string, mixed>, delay_ms: int}>
     */
    public static function build(
        string $keyword,
        string $conversationId,
        string $turnId,
        ?string $actionId = null
    ): array {
        $events = [self::event('meta', [
            'protocol' => 1,
            'conversation_id' => $conversationId,
            'turn_id' => $turnId,
        ])];

        if (isset(self::ERROR_SCRIPTS[$keyword])) {
            return array_merge($events, self::errorScript($keyword));
        }

        return array_merge($events, match ($keyword) {
            'tools' => self::toolsScript(),
            'slow' => self::slowScript(),
            'confirm' => self::confirmScript($actionId),
            default => self::textScript($keyword),
        });
    }

    /**
     * Ausfuehrung nach der Bestaetigung (W2 fuellt den echten Inhalt).
     *
     * @return list<array{event: string, data: array<string, mixed>, delay_ms: int}>
     */
    public static function buildConfirmExecution(
        string $conversationId,
        string $turnId,
        ?string $error = null
    ): array {
        $events = [self::event('meta', [
            'protocol' => 1,
            'conversation_id' => $conversationId,
            'turn_id' => $turnId,
        ])];

        if ($error !== null) {
            $events[] = self::event('error', [
                'code' => 'internal',
                'message' => $error,
                'retryable' => false,
            ], 100);
            $events[] = self::event('done', ['reason' => 'error'], 50);

            return $events;
        }

        $events[] = self::event('text.delta', ['text' => 'Die Aktion wurde ausgefuehrt.'], 200);
        $events[] = self::event('done', ['reason' => 'completed'], 100);

        return $events;
    }

    /** @return list<array{event: string, data: array<string, mixed>, delay_ms: int}> */
    private static function textScript(string $keyword): array
    {
        $events = self::deltas(self::TEXT_SCRIPTS[$keyword] ?? self::TEXT_SCRIPTS[self::DEFAULT_SCRIPT]);
        $events[] = self::event('done', ['reason' => 'completed'], 120);

        return $events;
    }

    /** @return list<array{event: string, data: array<string, mixed>, delay_ms: int}> */
    private static function toolsScript(): array
    {
        return array_merge(
            self::deltas(['Ich sehe im Dienstplan nach.'], 150),
            [
                self::event('tool.call', [
                    'id' => 'call_1',
                    'name' => 'find_shifts',
                    'label' => 'Suche Schichten der naechsten Woche',
                ], 300),
                self::event('tool.result', ['id' => 'call_1', 'status' => 'ok'], 900),
            ],
            self::deltas([
                "\n\nGefunden: ",
                '**3 Schichten** am Wochenende. ',
                'Details: [Dienstplan](/shifts/plan).',
            ], 150),
            [self::event('done', ['reason' => 'completed'], 120)],
        );
    }

    /**
     * Langsam: 18 s Denkpause vor dem ersten Delta, danach alle 2 s eines. Die Pause ist laenger
     * als das Heartbeat-Intervall (15 s, 0.3 §1) — der Stream enthaelt also genau einen `: ping`,
     * und die Bar muss waehrenddessen im Zustand `streaming` bleiben.
     *
     * @return list<array{event: string, data: array<string, mixed>, delay_ms: int}>
     */
    private static function slowScript(): array
    {
        $events = self::deltas(['Ich denke nach... '], 18000);

        for ($i = 1; $i <= 6; $i++) {
            $events[] = self::event('text.delta', ['text' => "Teil {$i} der langsamen Antwort. "], 2000);
        }

        $events[] = self::event('done', ['reason' => 'completed'], 500);

        return $events;
    }

    /** @return list<array{event: string, data: array<string, mixed>, delay_ms: int}> */
    private static function confirmScript(?string $actionId = null): array
    {
        return array_merge(
            self::deltas(['Ich habe zwei Aenderungen vorbereitet.'], 200),
            [
                self::event('confirm.request', [
                    // Steht in der Nachricht eine UUID, ist das die Aktion — so kann ein Dusk-Lauf
                    // eine echte, in artwork angelegte Aktion bestaetigen (core-ki-bar-confirm).
                    'action_id' => $actionId ?? (string) Str::uuid(),
                    'tool' => 'create_event',
                    'expires_at' => Carbon::now()->addMinutes(5)->toIso8601String(),
                    'preview' => [
                        'summary' => 'Zwei Termine anlegen, einen verschieben.',
                        'items' => [
                            ['action' => 'create', 'label' => 'Probe am 20.09., 10:00-14:00, Studio 1'],
                            [
                                'action' => 'update',
                                'label' => 'Aufbau am 21.09. auf 08:00 vorziehen',
                                'conflicts' => ['Studio 1 ist von 08:00 bis 09:00 belegt'],
                            ],
                        ],
                    ],
                ], 400),
                self::event('done', ['reason' => 'awaiting_confirmation'], 100),
            ],
        );
    }

    /** @return list<array{event: string, data: array<string, mixed>, delay_ms: int}> */
    private static function errorScript(string $keyword): array
    {
        [$code, $retryable, $retryAfter, $message] = self::ERROR_SCRIPTS[$keyword];

        $data = ['code' => $code, 'message' => $message, 'retryable' => $retryable];

        if ($retryAfter !== null) {
            $data['retry_after_seconds'] = $retryAfter;
        }

        return [
            self::event('error', $data, 200),
            self::event('done', ['reason' => 'error'], 50),
        ];
    }

    /**
     * @param list<string> $texts
     * @return list<array{event: string, data: array<string, mixed>, delay_ms: int}>
     */
    private static function deltas(array $texts, int $delayMs = 180): array
    {
        return array_map(
            static fn (string $text): array => self::event('text.delta', ['text' => $text], $delayMs),
            $texts,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array{event: string, data: array<string, mixed>, delay_ms: int}
     */
    private static function event(string $event, array $data, int $delayMs = 0): array
    {
        return ['event' => $event, 'data' => $data, 'delay_ms' => $delayMs];
    }
}
