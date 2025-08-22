<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ChatMessageRead extends Model
{

    use Prunable;

    protected $fillable = ['message_id', 'user_id', 'read_at'];

    protected $casts = [
        'read_at' => TranslatedDateTimeCast::class,
    ];

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id', 'id', 'message');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'sender');
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(3));
    }
}
