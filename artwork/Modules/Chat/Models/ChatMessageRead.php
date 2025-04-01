<?php

namespace Artwork\Modules\Chat\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageRead extends Model
{
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
}
