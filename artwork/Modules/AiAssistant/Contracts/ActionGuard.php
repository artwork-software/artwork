<?php

namespace Artwork\Modules\AiAssistant\Contracts;

use Artwork\Modules\User\Models\User;

/**
 * Pruefung, ob eine bestaetigte Aktion ausgefuehrt werden darf (Konzept W2 §2).
 *
 * In diesem Paket (W1) existiert nur der Platzhalter `RejectingActionGuard`, der jede Ausfuehrung
 * ablehnt. `core-confirm-actions` (W2) ersetzt die Bindung durch die echte Pruefung: Aktion gehoert
 * dem Token-User, `jti` des Tokens stimmt, `args_hash` stimmt, Status `awaiting_confirmation`,
 * nicht abgelaufen.
 */
interface ActionGuard
{
    /**
     * @param array<string, mixed> $args
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException wenn nicht ausfuehrbar
     */
    public function assertExecutable(User $user, string $actionId, string $tool, array $args): void;

    /**
     * Nach erfolgreicher Ausfuehrung, **in derselben Transaktion** (W2 §3): Aktion auf `executed`
     * setzen, Batch vermerken und das Write-Token per `jti` revozieren. Ein zweiter Aufruf mit
     * derselben Aktion scheitert danach in `assertExecutable()`.
     */
    public function complete(User $user, string $actionId, ?string $batchId): void;
}
