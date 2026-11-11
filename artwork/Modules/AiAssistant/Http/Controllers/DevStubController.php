<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Support\DevStubScripts;
use Artwork\Modules\AiAssistant\Support\SseEventSchemaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entwicklungs-Stub fuer die KI-Bar (core-ki-bar-ui, Konzept C4 §9).
 *
 * Streamt feste Event-Skripte nach Protokoll 1, damit die Bar ohne artwork-ai gebaut, manuell
 * geprueft und (core-ki-bar-stub-test) mit Dusk getestet werden kann. Die Routen registriert der
 * AiAssistantServiceProvider ausschliesslich in der Umgebung `local`.
 *
 * Bewusst kein Ersatz fuer artwork-ai: das Token wird nicht verifiziert, nur auf Anwesenheit
 * geprueft — so faellt hier schon auf, wenn die Bar Authorization oder X-Artwork-Instance
 * vergisst.
 */
class DevStubController extends Controller
{
    /** Heartbeat-Intervall in Sekunden (Konzept 0.3 §1). */
    private const HEARTBEAT_SECONDS = 15;

    /** Laenge einer Warteschleife in Mikrosekunden — bestimmt, wie schnell ein Abbruch auffaellt. */
    private const SLEEP_SLICE_MICROSECONDS = 200000;

    /** Lebensdauer der Aufrufzaehler (core-ki-bar-stub-test). */
    private const CALL_COUNTER_TTL_SECONDS = 3600;

    public function __construct(private readonly SseEventSchemaValidator $validator)
    {
    }

    public function chat(Request $request): StreamedResponse|JsonResponse
    {
        if (($unauthorized = $this->rejectWithoutCredentials($request)) !== null) {
            return $unauthorized;
        }

        // Body nach 0.3 §2. Bewusst kein FormRequest und kein $request->validate(): die Route
        // laeuft ohne die web-Gruppe, ein Redirect auf eine Fehlerseite waere fuer einen
        // fetch-Client nutzlos — der Stub antwortet wie artwork-ai mit JSON.
        $validator = ValidatorFacade::make($request->all(), [
            'conversation_id' => ['nullable', 'uuid'],
            'message' => ['required', 'string', 'max:4000'],
            'client_turn_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => 'invalid_request', 'messages' => $validator->errors()->all()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /** @var array{conversation_id?: string|null, message: string, client_turn_id: string} $validated */
        $validated = $validator->validated();

        $this->countCall($validated['client_turn_id']);

        return $this->stream(DevStubScripts::build(
            DevStubScripts::resolveKeyword($validated['message']),
            $validated['conversation_id'] ?? (string) Str::uuid(),
            (string) Str::uuid(),
            $this->actionIdIn($validated['message']),
        ));
    }

    /**
     * Bestaetigungs-Turn (Konzept W2 §5). Der Stub steht hier fuer artwork-ai und macht deshalb
     * genau dessen zwei Schritte (W2 §7, Schritt 5): Aktion holen, Schreib-Endpunkt mit
     * `dry_run=false` aufrufen — beides mit dem mitgeschickten Write-Token. Danach streamt er ein
     * festes Ergebnis-Skript. Ohne diese beiden Schritte liefe der Stub an der eigentlichen Kette
     * vorbei, und ein Dusk-Lauf koennte „Rueckgaengig" nie sehen.
     */
    public function confirm(Request $request, string $action): StreamedResponse|JsonResponse
    {
        if (($unauthorized = $this->rejectWithoutCredentials($request)) !== null) {
            return $unauthorized;
        }

        $error = $this->executeAction($request, $action);

        return $this->stream(DevStubScripts::buildConfirmExecution(
            (string) $request->input('conversation_id', (string) Str::uuid()),
            (string) Str::uuid(),
            $error,
        ));
    }

    /** UUID in der Nachricht = Aktion, die der Stub bestaetigen lassen soll. */
    private function actionIdIn(string $message): ?string
    {
        preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $message, $matches);

        return $matches[0] ?? null;
    }

    /**
     * Fuehrt die bestaetigte Aktion aus wie artwork-ai. Rueckgabe ist die Fehlermeldung fuers
     * Skript oder null.
     *
     * Bewusst ueber den HTTP-Kernel derselben Anwendung statt ueber einen echten Request: der
     * Entwicklungsserver beantwortet nur eine Anfrage zugleich, ein Selbstaufruf ueber das Netz
     * wuerde blockieren. Middleware, Scopes und Pruefkette laufen dabei vollstaendig.
     */
    private function executeAction(Request $request, string $action): ?string
    {
        $token = (string) $request->header('Authorization');

        $actionResponse = $this->subRequest('GET', '/api/v1/ai/actions/' . $action, null, $token);

        if ($actionResponse->getStatusCode() !== Response::HTTP_OK) {
            return 'Aktion nicht abrufbar (' . $actionResponse->getStatusCode() . ').';
        }

        /** @var array{tool?: string, args?: array<string, mixed>} $payload */
        $payload = json_decode((string) $actionResponse->getContent(), true) ?: [];
        $args = (array) ($payload['args'] ?? []);
        $target = $this->endpointFor((string) ($payload['tool'] ?? ''), $args);

        if ($target === null) {
            return 'Unbekanntes Werkzeug.';
        }

        $writeResponse = $this->subRequest(
            $target[0],
            $target[1],
            array_merge($args, ['dry_run' => false, 'action_id' => $action]),
            $token
        );

        return $writeResponse->getStatusCode() === Response::HTTP_OK
            ? null
            : 'Ausfuehrung fehlgeschlagen (' . $writeResponse->getStatusCode() . ').';
    }

    /**
     * @param array<string, mixed> $args
     * @return array{0: string, 1: string}|null
     */
    private function endpointFor(string $tool, array $args): ?array
    {
        return match ($tool) {
            'create_events' => ['POST', '/api/v1/ai/events/batch'],
            'delete_events' => ['DELETE', '/api/v1/ai/events/batch'],
            'update_event' => ['PATCH', '/api/v1/ai/events/' . (int) ($args['event_id'] ?? 0)],
            default => null,
        };
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function subRequest(string $method, string $path, ?array $body, string $authorization): SymfonyResponse
    {
        $subRequest = Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => $authorization],
            $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR)
        );

        if ($body !== null) {
            $subRequest->headers->set('Content-Type', 'application/json');
        }

        $outer = app('request');

        try {
            return app(HttpKernel::class)->handle($subRequest);
        } finally {
            // Der Kernel bindet `request` um; ohne das Zuruecksetzen liefe der Stream mit dem
            // Sub-Request weiter.
            app()->instance('request', $outer);
        }
    }

    /**
     * Wie oft wurde dieselbe `client_turn_id` gesendet? (core-ki-bar-stub-test)
     *
     * Der Dusk-Test weist damit nach, dass die Bar nach `token_expired` genau einmal wiederholt —
     * und zwar mit derselben Turn-ID (0.2 §4, 0.3 §5). Nur lesend, nur lokal.
     */
    public function calls(string $clientTurnId): JsonResponse
    {
        return response()->json(['count' => (int) Cache::get(self::callKey($clientTurnId), 0)]);
    }

    /** Zaehlt einen Aufruf; die Werte sind Wegwerfzustand und laufen nach einer Stunde aus. */
    private function countCall(string $clientTurnId): void
    {
        $key = self::callKey($clientTurnId);

        // add() legt den Schluessel mit TTL an, increment() erhoeht ihn danach ohne die TTL zu verlieren.
        Cache::add($key, 0, self::CALL_COUNTER_TTL_SECONDS);
        Cache::increment($key);
    }

    private static function callKey(string $clientTurnId): string
    {
        return 'ai-dev-stub:calls:' . $clientTurnId;
    }

    /** 401 wie artwork-ai (contracts/token-contract.md), solange Header fehlen. */
    private function rejectWithoutCredentials(Request $request): ?JsonResponse
    {
        $hasBearer = Str::startsWith((string) $request->header('Authorization'), 'Bearer ');
        $hasInstance = trim((string) $request->header('X-Artwork-Instance')) !== '';

        if ($hasBearer && $hasInstance) {
            return null;
        }

        return response()->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param list<array{event: string, data: array<string, mixed>, delay_ms: int, raw?: bool}> $events
     */
    private function stream(array $events): StreamedResponse
    {
        $validate = $this->validator->available();

        return response()->stream(function () use ($events, $validate): void {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $lastOutput = microtime(true);

            foreach ($events as $event) {
                if (!$this->waitBefore($event['delay_ms'], $lastOutput)) {
                    return;
                }

                // `raw` markiert die bewusst protokollfremden Sonden aus Protokoll 1.1
                // (unbekanntes Event, unbekannter done.reason) — fuer alles andere bleibt die
                // Pruefung streng (core-ki-bar-choice).
                if ($validate && ($event['raw'] ?? false) !== true) {
                    $this->validator->assertValid($event['event'], $event['data']);
                }

                $this->send($event['event'], $event['data']);
                $lastOutput = microtime(true);
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Wartet die Pause vor einem Event ab und schiebt dabei Heartbeats ein.
     *
     * @return bool false, wenn der Client die Verbindung getrennt hat (0.3 §8)
     */
    private function waitBefore(int $delayMs, float &$lastOutput): bool
    {
        $until = microtime(true) + ($delayMs / 1000);

        while (microtime(true) < $until) {
            if (connection_aborted() === 1) {
                return false;
            }

            if (microtime(true) - $lastOutput >= self::HEARTBEAT_SECONDS) {
                $this->write(": ping\n\n");
                $lastOutput = microtime(true);
            }

            usleep(self::SLEEP_SLICE_MICROSECONDS);
        }

        return connection_aborted() !== 1;
    }

    /** @param array<string, mixed> $data */
    private function send(string $event, array $data): void
    {
        $this->write(
            'event: ' . $event . "\n"
            . 'data: ' . json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . "\n\n"
        );
    }

    /** Ausgabe sofort rausschieben — ohne Flush haelt PHP/FPM den Stream bis zum Ende. */
    private function write(string $chunk): void
    {
        echo $chunk;
        flush();
    }
}
