<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property int $order
 * @property string $created_at
 * @property string $updated_at
 */
class ProjectHeadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_project_headlines')->withPivot('text');
    }
}
