<?php

namespace Artwork\Modules\Department\Models;

use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Invitation;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Department extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'svg_name'
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsToMany
     */
    public function invitations(): BelongsToMany
    {
        return $this->belongsToMany(Invitation::class);
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * @return BelongsToMany
     */
    public function checklists(): BelongsToMany
    {
        return $this->belongsToMany(Checklist::class);
    }

    /**
     * @return BelongsToMany
     */
    public function checklist_templates(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplate::class);
    }

    /**
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
