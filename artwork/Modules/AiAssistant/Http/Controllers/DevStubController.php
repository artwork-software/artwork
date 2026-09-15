<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Support\DevStubScripts;
use Artwork\Modules\AiAssistant\Support\SseEventSchemaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
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

        return $this->stream(DevStubScripts::build(
            DevStubScripts::resolveKeyword($validated['message']),
            $validated['conversation_id'] ?? (string) Str::uuid(),
            (string) Str::uuid(),
        ));
    }

    public function confirm(Request $request, string $action): StreamedResponse|JsonResponse
    {
        if (($unauthorized = $this->rejectWithoutCredentials($request)) !== null) {
            return $unauthorized;
        }

        // Der Stub prueft die action_id nicht — W2 bringt die echte Pruef- und Revocation-Logik.
        unset($action);

        return $this->stream(DevStubScripts::buildConfirmExecution(
            (string) $request->input('conversation_id', (string) Str::uuid()),
            (string) Str::uuid(),
        ));
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
     * @param list<array{event: string, data: array<string, mixed>, delay_ms: int}> $events
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

                if ($validate) {
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
