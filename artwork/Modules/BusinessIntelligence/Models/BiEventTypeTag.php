<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BiEventTypeTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_de',
        'color',
    ];

    public function eventTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            EventType::class,
            'bi_event_type_tag_event_type',
            'bi_event_type_tag_id',
            'event_type_id'
        );
    }
}
