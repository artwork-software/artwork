<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ChatMessage extends Model
{
    use HasFactory;
    use Prunable;

    protected $fillable = [
        'chat_id', 'sender_id', 'message'
    ];

    protected $casts = [
        'created_at' => TranslatedDateTimeCast::class,
    ];

    protected $appends = [
        'created_at_iso',
        'created_at_date',
        'created_at_time',
    ];

    public function chat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id', 'chat');
    }

    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id', 'sender');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatMessageRead::class, 'message_id', 'id');
    }

    // ---- Appended Attributes ----
    public function getCreatedAtIsoAttribute(): ?string
    {
        $raw = $this->getRawOriginal('created_at');
        return $raw ? Carbon::parse($raw)->toISOString() : null;
    }

    public function getCreatedAtDateAttribute(): ?string
    {
        $raw = $this->getRawOriginal('created_at');
        if (!$raw) return null;

        $dt = Carbon::parse($raw)->locale('de');
        // Beispiel: Donnerstag 21.08.2025
        return $dt->translatedFormat('l d.m.Y');
    }

    public function getCreatedAtTimeAttribute(): ?string
    {
        $raw = $this->getRawOriginal('created_at');
        if (!$raw) return null;

        $dt = Carbon::parse($raw)->locale('de');
        // Beispiel: 14:37
        return $dt->translatedFormat('H:i');
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(3));
    }
}
