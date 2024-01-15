<?php

namespace Artwork\Modules\Department\Models;

use App\Models\ChecklistTemplate;
use App\Models\Invitation;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $svg_name
 */
class Department extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'svg_name'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }


    public function invitations(): BelongsToMany
    {
        return $this->belongsToMany(Invitation::class);
    }


    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }


    public function checklists(): BelongsToMany
    {
        return $this->belongsToMany(Checklist::class);
    }


    //@todo: fix phpcs error - refactor function name to checklistTemplates
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function checklist_templates(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplate::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
