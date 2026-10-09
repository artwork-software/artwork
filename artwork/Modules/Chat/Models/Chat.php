<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
