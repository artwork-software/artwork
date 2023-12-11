<?php

namespace App\Models;

use Artwork\Modules\Checklist\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
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

    public function checklist_templates(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
