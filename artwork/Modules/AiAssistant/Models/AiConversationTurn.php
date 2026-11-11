<?php

namespace Artwork\Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein abgeschlossener Turn (core-chat-historie; Konzept C3 §2).
 *
 * Bewusst ohne LogsActivity: Der Inhalt eines Gespraechs gehoert nicht ins Activity-Log.
 * tool_calls traegt nur Name, Anzeige-Label und Status — keine Argumente, keine Ergebnisse
 * (C3 §3), weil das eine zweite, ungeschuetzte Kopie policygeschuetzter Hausdaten waere.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string $client_turn_id
 * @property string $user_message
 * @property string|null $assistant_message
 * @property string $status
 * @property string|null $error_code
 * @property array<int, array<string, string>>|null $tool_calls
 * @property array<string, mixed>|null $context
 * @property array<string, mixed>|null $pending_action
 */
class AiConversationTurn extends Model
{
    use HasUuids;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    protected $fillable = [
        'conversation_id',
        'client_turn_id',
        'user_message',
        'assistant_message',
        'status',
        'error_code',
        'tool_calls',
        'context',
        'pending_action',
    ];

    protected $casts = [
        // Schuetzt Dumps und Backups. Kostet die SQL-Suche im Inhalt, die hier niemand braucht.
        'user_message' => 'encrypted',
        'assistant_message' => 'encrypted',
        'tool_calls' => 'array',
        'context' => 'array',
        'pending_action' => 'array',
    ];

    /** @return BelongsTo<AiConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
