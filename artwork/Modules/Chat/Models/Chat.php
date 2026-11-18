<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read ChatMessage|null $last_message
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_group',
        'is_archived',
        'is_favorite',
        'is_muted',
        'is_pinned',
        'created_by',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'is_archived' => 'boolean',
        'is_favorite' => 'boolean',
        'is_muted' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    protected $appends = [
        'last_message',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatMessage::class)->with('sender');
    }

    /**
     * @return HasOne<ChatMessage, $this>
     */
    public function lastMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChatMessage::class)->latest()->with('sender');
    }

    public function getLastMessageAttribute(): ?ChatMessage
    {
        return $this->lastMessage()->first();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id', 'creator');
    }

    /**
     * Echte Mitgliedschaft. Bewusst keine Policy: über Gate::before käme die Admin-Rolle sonst in
     * fremde Chats. Entspricht der Prüfung des Broadcast-Kanals chat.{id}.
     */
    public function hasMember(?User $user): bool
    {
        return $user !== null && $this->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Bricht mit 403 ab, wenn die Person kein Mitglied ist. Gilt auch für Admins.
     */
    public function abortUnlessMember(?User $user): void
    {
        abort_unless($this->hasMember($user), 403);
    }

    /**
     * Löschen darf, wer den Chat angelegt hat. Ist niemand (mehr) zuständig, weil die anlegende Person
     * fehlt, deaktiviert ist oder den Chat verlassen hat, darf jedes Mitglied löschen. So bleibt kein
     * Chat unlöschbar.
     */
    public function canBeDeletedBy(?User $user): bool
    {
        if (!$this->hasMember($user)) {
            return false;
        }

        if ((int) $this->created_by === $user->id) {
            return true;
        }

        $creator = $this->creator;

        return $creator === null || $creator->isDeactivated() || !$this->hasMember($creator);
    }
}
