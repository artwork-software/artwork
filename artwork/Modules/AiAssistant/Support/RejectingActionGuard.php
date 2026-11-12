<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platzhalter bis `core-confirm-actions` (W2): Ausfuehrung ist noch nicht freigeschaltet.
 *
 * Bewusst ein harter Ablehner statt „durchwinken": ohne die Pruefkette aus W2 §2 gaebe es keinerlei
 * Bindung zwischen bestaetigter Vorschau und ausgefuehrter Aenderung.
 */
final class RejectingActionGuard implements ActionGuard
{
    /**
     * @param array<string, mixed> $args
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
    public function assertExecutable(User $user, string $actionId, string $tool, array $args): void
    {
        abort(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ausfuehrung noch nicht freigeschaltet.'
        );
    }

    /**
     * Unerreichbar: `assertExecutable()` bricht vorher ab. Die Methode existiert nur, damit der
     * Platzhalter den Vertrag erfuellt.
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
    public function complete(User $user, string $actionId, ?string $batchId): void
    {
    }
}
