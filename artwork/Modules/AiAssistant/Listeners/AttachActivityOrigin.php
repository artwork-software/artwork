<?php

namespace Artwork\Modules\AiAssistant\Listeners;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passport\AccessToken;
use Spatie\Activitylog\Contracts\Activity;

/**
 * Haengt jedem Activity-Log-Eintrag an, auf welchem Weg er entstanden ist (core-via-activity-log;
 * Konzept C1 §6).
 *
 * Der Verursacher ist immer der echte Mensch — es gibt keinen KI-Systemuser. Was fehlte, war die
 * Herkunft: ob jemand in der Oberflaeche geklickt, ein Kunden-Key geschrieben oder der Assistent
 * im Auftrag gehandelt hat.
 *
 * Laeuft bei JEDEM Log-Eintrag im System. Die Reihenfolge der Pruefungen ist deshalb nach Kosten
 * sortiert: der haeufigste Fall (Oberflaeche) kommt ohne Datenbankzugriff und ohne Settings aus.
 */
class AttachActivityOrigin
{
    public const VIA_UI = 'ui';
    public const VIA_API = 'api';
    public const VIA_AI_ASSISTANT = 'ai_assistant';
    public const VIA_SYSTEM = 'system';

    public const CONVERSATION_HEADER = 'X-Ai-Conversation';

    public function handle(Activity $activity): void
    {
        [$via, $detail] = $this->resolveOrigin();

        // Ergaenzen, nie ersetzen: bestehende Properties eines Eintrags bleiben unangetastet.
        $activity->properties = $activity->properties->merge([
            'via' => $via,
            'via_detail' => $detail,
        ]);
    }

    /** @return array{0: string, 1: string|null} */
    private function resolveOrigin(): array
    {
        $request = $this->currentRequest();

        if ($request === null) {
            return [self::VIA_SYSTEM, null];
        }

        // Ohne Bearer-Header gar nicht erst an den api-Guard: Passports TokenGuard wertet sonst den
        // Passport-Cookie aus und liefert einen TransientToken — ein Klick in der Oberflaeche waere
        // damit faelschlich "api".
        if ($request->bearerToken() === null) {
            return Auth::check() ? [self::VIA_UI, null] : [self::VIA_SYSTEM, null];
        }

        $user = Auth::guard('api')->user();
        $token = $user?->token();

        if (!$token instanceof AccessToken) {
            return [self::VIA_SYSTEM, null];
        }

        if ($this->isAiSessionToken($token)) {
            return [self::VIA_AI_ASSISTANT, $this->conversationId($request)];
        }

        // Einziger Fall mit Datenbankzugriff: der Token-Name steht nur im Record, nicht im JWT.
        return [self::VIA_API, $token->name];
    }

    private function isAiSessionToken(AccessToken $token): bool
    {
        $clientId = app(AiAssistantSettings::class)->ai_session_client_id;

        if ($clientId === null) {
            return false;
        }

        // oauth_client_id kommt aus dem JWT (aud) und kostet keine Abfrage.
        return (string) $token->oauth_client_id === $clientId;
    }

    private function conversationId(Request $request): ?string
    {
        $value = $request->header(self::CONVERSATION_HEADER);

        return is_string($value) && Str::isUuid($value) ? $value : null;
    }

    /**
     * Bewusst nicht ueber runningInConsole(): Unter PHPUnit ist das immer wahr, waehrend zugleich
     * simulierte HTTP-Requests laufen. Der Konsolen- und Job-Fall faellt ohnehin durch — dort gibt
     * es weder einen Bearer-Header noch eine angemeldete Session, also `system`.
     */
    private function currentRequest(): ?Request
    {
        if (!app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }
}
