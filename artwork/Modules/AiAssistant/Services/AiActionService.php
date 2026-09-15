<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

/**
 * Der Bestaetigungs-Pfad in artwork (core-confirm-actions; Konzept W2).
 *
 * **Der Turn ist die Aktion.** artwork-ai bleibt zustandslos: es schreibt einen Turn mit Status
 * `awaiting_confirmation` und `pending_action`, und jede weitere Pruefung passiert hier.
 *
 * Eigentuemerschaft wird **direkt** geprueft, nie ueber ein Gate: `Gate::before` gaebe
 * Artwork-Admins jede Abfrage frei, und eine fremde Aktion darf auch ein Admin nicht ausfuehren
 * (HANDOVER_core §6).
 */
class AiActionService
{
    public const STATUS_EXECUTED = 'executed';

    public const STATUS_EXPIRED = 'expired';

    public function __construct(private readonly AiAssistantAccess $access)
    {
    }

    /**
     * Aktion fuer die Token-Ausstellung (W2 §2): eigene Aktion, offen, nicht abgelaufen, Stufe C.
     */
    public function forTokenIssuance(User $user, string $actionId): AiConversationTurn
    {
        $turn = $this->findOrFail($user, $actionId);

        abort_unless(
            $this->access->levelCAllowed($user),
            Response::HTTP_FORBIDDEN,
            'Aktionen des Assistenten sind in diesem Haus nicht freigeschaltet.'
        );
        $this->assertOpen($turn);

        return $turn;
    }

    /**
     * `jti` des ausgestellten Write-Tokens an die Aktion binden (W2 §2).
     */
    public function bindToken(AiConversationTurn $turn, string $jti): void
    {
        $turn->forceFill([
            'pending_action' => array_merge($turn->pending_action ?? [], ['jti' => $jti]),
        ])->save();
    }

    /**
     * Pruefkette des Schreib-Endpunkts (W2 §2). Reihenfolge und Statuscodes nach W2 §8.
     *
     * @param array<string, mixed> $args
     */
    public function assertExecutable(User $user, string $actionId, string $tool, array $args, ?string $jti): void
    {
        $turn = $this->findOrFail($user, $actionId);
        $action = $turn->pending_action ?? [];

        // Zweite Ausfuehrung: die Aktion steht schon auf `executed`.
        abort_if(
            $turn->status === self::STATUS_EXECUTED,
            Response::HTTP_CONFLICT,
            'Diese Aktion wurde bereits ausgefuehrt.'
        );
        $this->assertOpen($turn);

        // Reihenfolge und Statuscodes nach contracts/action-contract.md: eine nicht passende jti
        // ist ein Authentifizierungsfehler (401), ein falsches Werkzeug ein Eingabefehler (422).
        abort_unless(
            is_string($action['jti'] ?? null) && $jti !== null && hash_equals((string) $action['jti'], $jti),
            Response::HTTP_UNAUTHORIZED,
            'Das Token gehoert nicht zu dieser Aktion.'
        );

        abort_unless(
            ($action['tool'] ?? null) === $tool,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Die Aktion gehoert zu einem anderen Werkzeug.'
        );

        abort_unless(
            is_string($action['args_hash'] ?? null) && AiActionHash::matches($args, (string) $action['args_hash']),
            Response::HTTP_CONFLICT,
            'Die Argumente weichen von der bestaetigten Vorschau ab.'
        );
    }

    /**
     * Abschluss in derselben Transaktion wie die Ausfuehrung (W2 §3): Status, Batch, Revocation.
     */
    public function complete(User $user, string $actionId, ?string $batchId): void
    {
        $turn = $this->findOrFail($user, $actionId);
        $action = $turn->pending_action ?? [];

        $turn->forceFill([
            'status' => self::STATUS_EXECUTED,
            'pending_action' => array_merge($action, [
                'executed_at' => CarbonImmutable::now()->toIso8601String(),
                'batch_id' => $batchId,
            ]),
        ])->save();

        $this->releaseConversation($turn);

        // Single-Use: das Write-Token ist mit der Ausfuehrung verbraucht (W2 §3).
        if (is_string($action['jti'] ?? null)) {
            Passport::token()->newQuery()->whereKey($action['jti'])->update(['revoked' => true]);
        }
    }

    /**
     * Ablehnung aus der Bar (W2 §6): kein Token, kein Aufruf an artwork-ai.
     */
    public function cancel(User $user, string $actionId): AiConversationTurn
    {
        $turn = $this->findOrFail($user, $actionId);

        abort_if(
            $turn->status === self::STATUS_EXECUTED,
            Response::HTTP_CONFLICT,
            'Diese Aktion wurde bereits ausgefuehrt.'
        );

        if ($turn->status === AiConversationTurn::STATUS_AWAITING_CONFIRMATION) {
            $turn->forceFill(['status' => AiConversationTurn::STATUS_CANCELLED])->save();
            $this->releaseConversation($turn);
        }

        return $turn;
    }

    /**
     * Ablauf-Job: offene Aktionen, deren Frist verstrichen ist, werden `expired` und geben ihr
     * Gespraech wieder frei.
     */
    public function expireDue(): int
    {
        $expired = 0;

        AiConversationTurn::query()
            ->where('status', AiConversationTurn::STATUS_AWAITING_CONFIRMATION)
            ->whereNotNull('pending_action')
            ->chunkById(200, function ($turns) use (&$expired): void {
                foreach ($turns as $turn) {
                    if (!$this->hasExpired($turn)) {
                        continue;
                    }

                    DB::transaction(function () use ($turn): void {
                        $turn->forceFill(['status' => self::STATUS_EXPIRED])->save();
                        $this->releaseConversation($turn);
                    });

                    $expired++;
                }
            });

        return $expired;
    }

    /**
     * Die Aktion fuer artwork-ai (W2 §4) — ohne `jti`, der bleibt in artwork.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(User $user, string $actionId): array
    {
        $turn = $this->findOrFail($user, $actionId);
        $action = $turn->pending_action ?? [];

        unset($action['jti']);

        return array_merge($action, ['id' => $turn->id, 'status' => $turn->status]);
    }

    private function findOrFail(User $user, string $actionId): AiConversationTurn
    {
        $turn = AiConversationTurn::query()->with('conversation')->find($actionId);

        abort_if($turn === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Unbekannte Aktion.');

        // Fremde Aktion ist hier ein 403 und kein 404 (W2 §8): der Aufrufer haelt bereits ein
        // Token, die Existenz einer Turn-ID ist damit keine neue Information.
        abort_unless(
            $turn->conversation?->user_id === $user->id,
            Response::HTTP_FORBIDDEN,
            'Diese Aktion gehoert zu einer anderen Person.'
        );

        abort_if(
            ($turn->pending_action ?? []) === [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Zu diesem Turn gibt es keine bestaetigungspflichtige Aktion.'
        );

        return $turn;
    }

    private function assertOpen(AiConversationTurn $turn): void
    {
        abort_unless(
            $turn->status === AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Diese Aktion wartet nicht mehr auf eine Bestaetigung.'
        );

        abort_if(
            $this->hasExpired($turn),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Die Bestaetigungsfrist ist abgelaufen.'
        );
    }

    private function hasExpired(AiConversationTurn $turn): bool
    {
        $expiresAt = $turn->pending_action['expires_at'] ?? null;

        if (!is_string($expiresAt)) {
            return false;
        }

        return CarbonImmutable::parse($expiresAt)->isPast();
    }

    private function releaseConversation(AiConversationTurn $turn): void
    {
        $conversation = $turn->conversation;

        if (
            $conversation instanceof AiConversation
            && $conversation->status !== AiConversation::STATUS_ACTIVE
        ) {
            $conversation->forceFill(['status' => AiConversation::STATUS_ACTIVE])->save();
        }
    }
}
