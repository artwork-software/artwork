<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function lastMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChatMessage::class)->latest();
    }

    public function getLastMessageAttribute(): Model|null
    {
        return $this->lastMessage()->first();
    }


}
