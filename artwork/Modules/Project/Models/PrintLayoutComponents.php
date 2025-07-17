<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintLayoutComponents extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_print_layout_id',
        'component_id',
        'type',
        'position',
        'row',
    ];


    public function projectPrintLayout(): BelongsTo
    {
        return $this->belongsTo(
            ProjectPrintLayout::class,
            'project_print_layout_id',
            'id',
            'project_print_layouts'
        );
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(
            Component::class,
            'component_id',
            'id',
            'components'
        );
    }
}
