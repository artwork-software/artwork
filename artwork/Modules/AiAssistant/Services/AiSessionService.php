<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Passport\AiSessionTokenFactory;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;

/**
 * Stellt ein KI-Session-Token aus (core-session-token; Konzept 0.2 §3, C2 §4).
 *
 * Die erlaubte Scope-Menge bestimmt ausschliesslich der Server aus den Haus-Settings. Ein
 * angefragter Scope kann die Menge nur verkleinern, nie erweitern.
 */
class AiSessionService
{
    /** Konzept 0.2 §4 — muss zur TTL des Grants in AiAssistantServiceProvider passen. */
    private const TTL_MINUTES = 15;

    public function __construct(
        private readonly AiSessionTokenFactory $factory,
        private readonly AiAssistantSettings $settings,
        private readonly AiAssistantAccess $access,
    ) {
    }

    /**
     * @param string[] $requestedScopes leer = alle erlaubten
     * @return array{access_token: string, expires_at: string, scopes: string[]}
     */
    public function issue(User $user, array $requestedScopes = []): array
    {
        $scopes = $this->resolveScopes($user, $requestedScopes);

        $result = $this->factory->make($user->id, 'ai-session', $scopes);

        return [
            'access_token' => $result->accessToken,
            'expires_at' => CarbonImmutable::now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'scopes' => $scopes,
        ];
    }

    /**
     * ai:transcript immer, ai:read nur bei Stufe B. ai:write nie — der Schreibpfad braucht eine
     * Aktions-Bindung und kommt mit W2; der FormRequest lehnt ihn vorher mit 422 ab.
     *
     * @param string[] $requestedScopes
     * @return string[]
     */
    private function resolveScopes(User $user, array $requestedScopes): array
    {
        $allowed = [AiScopes::TRANSCRIPT];

        if ($this->access->levelBAllowed($user)) {
            $allowed[] = AiScopes::READ;
        }

        if ($requestedScopes === []) {
            return $allowed;
        }

        return array_values(array_intersect($allowed, $requestedScopes));
    }
}
