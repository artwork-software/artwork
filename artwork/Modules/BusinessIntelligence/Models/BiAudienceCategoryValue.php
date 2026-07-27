<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Erfasster Wert einer Besucher*innen-Kategorie.
 * event_id null = Gesamtwert (TOTAL-Modus), sonst Pro-Termin-Wert.
 * scope trennt Ist ('actual') und Plan ('plan').
 */
class BiAudienceCategoryValue extends Model
{
    use HasFactory;

    protected $table = 'bi_audience_category_values';

    protected $fillable = [
        'project_id',
        'bi_audience_category_id',
        'event_id',
        'scope',
        'quantity',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BiAudienceCategory::class, 'bi_audience_category_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }
}
