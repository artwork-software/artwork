<?php

namespace Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiProjectData extends Model
{
    use HasFactory;

    protected $table = 'bi_project_data';

    protected $fillable = [
        'project_id',
        'visitor_mode',
        'visitors_total',
        'sold_tickets_mode',
        'sold_tickets_total',
        'revenue_mode',
        'revenue_total',
        'is_new_production',
        'is_co_production',
        'is_own_production',
        'is_germany_premiere',
        'premiere_date',
    ];

    protected function casts(): array
    {
        return [
            'visitor_mode' => BiVisitorModeEnum::class,
            'sold_tickets_mode' => BiVisitorModeEnum::class,
            'revenue_mode' => BiVisitorModeEnum::class,
            'is_new_production' => 'boolean',
            'is_co_production' => 'boolean',
            'is_own_production' => 'boolean',
            'is_germany_premiere' => 'boolean',
            'premiere_date' => 'date',
            'revenue_total' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }
}
