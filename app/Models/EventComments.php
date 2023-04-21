<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventComments extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'comment',
        'is_admin_comment'
    ];

    protected $casts = [
        'is_admin_comment' => 'boolean',
        'created_at' => 'datetime: d.m.Y H:i'
    ];

    protected $with = [
        'user'
    ];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
