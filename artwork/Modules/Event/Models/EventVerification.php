<?php

namespace Artwork\Modules\Event\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EventVerification extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'verifier_id', 'verifier_type', 'status', 'rejection_reason'];

    public function verifier(): MorphTo
    {
        return $this->morphTo();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }
}
