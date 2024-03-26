<?php

namespace Artwork\Modules\ProjectTab\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    protected $with = ['components'];

    public function components(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ComponentInTab::class, 'project_tab_id', 'id');
    }
}
