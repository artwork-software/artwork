<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiEventData extends Model
{
    use HasFactory;

    protected $table = 'bi_event_data';

    protected $fillable = [
        'project_id',
        'event_id',
        'scope',
        'visitors',
        'sold_tickets',
        'revenue',
    ];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }
}
