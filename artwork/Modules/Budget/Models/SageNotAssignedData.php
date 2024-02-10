<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SageNotAssignedData extends Model
{
    use HasFactory;

    protected $fillable = [
        'sage_id',
        'project_id',
        'kto',
        'kst',
        'cost_center',
        'description',
        'amount'
    ];


    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }
}
