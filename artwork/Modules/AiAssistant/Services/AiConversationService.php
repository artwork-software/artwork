<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Zugriff auf Gespraeche und Turns (core-chat-historie).
 *
 * Jede Abfrage ist von vornherein auf den eigenen User eingeschraenkt. Dadurch liefert eine
 * fremde ID ein 404 und nicht ein 403 — die blosse Existenz einer Conversation-ID ist sonst
 * abfragbar (Konzept C3, Brief: „keine Existenzpreisgabe").
 */
class AiConversationService
{
    public const DEFAULT_TURN_LIMIT = 20;
    public const MAX_TURN_LIMIT = 50;

    public function findForUserOrFail(User $user, string $conversationId): AiConversation
    {
        return AiConversation::query()
            ->where('user_id', $user->id)
            ->findOrFail($conversationId);
    }

    public function create(User $user, string $title): AiConversation
    {
        return AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
    }

    /**
     * Die letzten N Turns, aufsteigend sortiert zurueckgegeben — das Modell bekommt sie in
     * Leserichtung, ohne dass der Aufrufer drehen muss.
     *
     * @return Collection<int, AiConversationTurn>
     */
    public function latestTurns(AiConversation $conversation, int $limit): Collection
    {
        $limit = max(1, min($limit, self::MAX_TURN_LIMIT));

        return $conversation->turns()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy([['created_at', 'asc'], ['id', 'asc']])
            ->values();
    }

    /**
     * Idempotent: Ist die client_turn_id in diesem Gespraech bekannt, kommt der bestehende Turn
     * zurueck — auch wenn der Body abweicht. Ein Wiederholungsversuch nach Netzabbruch darf keinen
     * zweiten Eintrag erzeugen und auch nicht den ersten ueberschreiben.
     *
     * @param array<string, mixed> $attributes
     * @return array{0: AiConversationTurn, 1: bool} Turn und ob er neu angelegt wurde
     */
    public function recordTurn(AiConversation $conversation, array $attributes): array
    {
        $existing = $conversation->turns()
            ->where('client_turn_id', $attributes['client_turn_id'])
            ->first();

        if ($existing !== null) {
            return [$existing, false];
        }

        return DB::transaction(function () use ($conversation, $attributes): array {
            $turn = $conversation->turns()->create([
                'client_turn_id' => $attributes['client_turn_id'],
                'user_message' => $attributes['user_message'],
                'assistant_message' => $attributes['assistant_message'] ?? null,
                'status' => $attributes['status'],
                'error_code' => $attributes['error_code'] ?? null,
                'tool_calls' => $attributes['tool_calls'] ?? [],
                'context' => $attributes['context'] ?? [],
            ]);

            $conversation->forceFill(['last_turn_at' => $turn->created_at])->save();

            return [$turn, true];
        });
    }
}
