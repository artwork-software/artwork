<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Services\AiActionService;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\AccessToken;

/**
 * Die echte Pruefung des Bestaetigungs-Pfades (core-confirm-actions; Konzept W2 §2, §3).
 *
 * Ersetzt den Platzhalter aus W1. Die `jti` kommt aus dem aufrufenden Token und kostet keine
 * Abfrage — sie steht als Claim im JWT.
 */
final class PendingActionGuard implements ActionGuard
{
    public function __construct(private readonly AiActionService $actions)
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function assertExecutable(User $user, string $actionId, string $tool, array $args): void
    {
        $this->actions->assertExecutable($user, $actionId, $tool, $args, $this->jtiOf($user));
    }

    public function complete(User $user, string $actionId, ?string $batchId): void
    {
        $this->actions->complete($user, $actionId, $batchId);
    }

    private function jtiOf(User $user): ?string
    {
        $token = $user->token();

        return $token instanceof AccessToken ? (string) $token->oauth_access_token_id : null;
    }
}
