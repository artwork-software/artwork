<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BiEventTypeTag extends Model
{
    use HasFactory;

    /** Steuert die Kennzahl "Vorstellungen" (und die Kapazitätsbasis der Auslastung). */
    public const KPI_ROLE_PERFORMANCE = 'performance';

    /** Steuert die Kennzahl "Veranstaltungstage". */
    public const KPI_ROLE_EVENT_DAY = 'event_day';

    public const KPI_ROLES = [self::KPI_ROLE_PERFORMANCE, self::KPI_ROLE_EVENT_DAY];

    protected $fillable = [
        'name',
        'name_de',
        'color',
        'kpi_role',
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
