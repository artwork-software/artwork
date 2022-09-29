<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $svg_name
 *
 * @property \Illuminate\Database\Eloquent\Collection<User> $users
 * @property \Illuminate\Database\Eloquent\Collection<Invitation> $invitations
 * @property \Illuminate\Database\Eloquent\Collection<Project> $projects
 * @property \Illuminate\Database\Eloquent\Collection<Checklist> $checklists
 * @property \Illuminate\Database\Eloquent\Collection<ChecklistTemplate> $checklist_templates
 */
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

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function invitations()
    {
        return $this->belongsToMany(Invitation::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function checklists()
    {
        return $this->belongsToMany(Checklist::class);
    }

    public function checklist_templates()
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
