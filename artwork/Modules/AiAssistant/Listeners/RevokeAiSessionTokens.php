<?php

namespace Artwork\Modules\AiAssistant\Listeners;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Laravel\Passport\Passport;

/**
 * Widerruft offene KI-Session-Tokens beim Abmelden und beim Zuruecksetzen des Passworts
 * (core-session-token; Konzept 0.2 §4).
 *
 * Ohne diesen Listener ueberlebt ein Token die Web-Session um bis zu 15 Minuten — artwork-ai
 * koennte in dieser Zeit im Namen einer abgemeldeten Person weiterarbeiten.
 *
 * Bewusst auf den ai-session-Client eingeschraenkt: Kunden-API-Keys haengen nicht an der
 * Web-Session und duerfen von einer Abmeldung nicht getroffen werden.
 */
class RevokeAiSessionTokens
{
    public function __construct(private readonly AiAssistantSettings $settings)
    {
    }

    public function handle(Logout|PasswordReset $event): void
    {
        $clientId = $this->settings->ai_session_client_id;
        $user = $event->user;

        if ($clientId === null || $user === null) {
            return;
        }

        Passport::token()->newQuery()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('client_id', $clientId)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }
}
