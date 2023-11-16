<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Modules\Checklist\Models\Checklist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $shift_description
 * @property string $key_visual_path
 * @property int $number_of_participants
 * @property string $cost_center
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property Collection|User[] $users
 * @property Collection|User[] $access_budget
 * @property Collection|User> $managerUsers
 * @property Collection|User> $writeUsers
 * @property Collection|Event[] $events
 * @property Collection|Department[] $departments
 * @property Collection|ProjectHistory[] $project_histories
 * @property Collection|Checklist[] $checklists
 * @property Collection|ProjectFile[] $project_files
 * @property Collection|Comment[] $comments
 * @property Collection|Category[] $categories
 * @property Collection|Sector[] $sectors
 * @property Collection|Genre[] $genres
 * @property Collection|Project[] $groups
 * @property Collection|Room[] $rooms
 * @property Sector $sector
 * @property Category $category
 * @property Genre $genre
 */
class Project extends Model
{
    use HasFactory, SoftDeletes, Prunable, Searchable, HasChangesHistory;

    protected $fillable = [
        'name',
        'description',
        'shift_description',
        'number_of_participants',
        'cost_center_id',
        'copyright_id',
        'key_visual_path',
        'state',
        'num_of_guests',
        'entry_fee',
        'registration_required',
        'register_by',
        'registration_deadline',
        'closed_society'
    ];

    protected $casts = [
        'registration_required' => 'boolean',
        'closed_society' => 'boolean'
    ];

    protected $with = ['shiftRelevantEventTypes', 'state'];


    public function cost_center()
    {
        return $this->hasOne(CostCenter::class);
    }

    public function shiftRelevantEventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'project_shift_relevant_event_types');
    }

    public function shift_contact(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_shift_contacts');
    }

    public function copyright()
    {
        return $this->hasOne(Copyright::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->withPivot('access_budget', 'is_manager', 'can_write', 'delete_permission');
    }

    public function headlines()
    {
        return $this->belongsToMany(ProjectHeadline::class, 'project_project_headlines', 'project_id')
            ->withPivot('text');
    }

    public function access_budget()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('access_budget', true);
    }

    public function writeUsers():BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('can_write', true);
    }

    public function delete_permission_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('delete_permission', true);
    }

    public function managerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('is_manager', true);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function project_histories()
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function project_files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function sectors()
    {
        return $this->belongsToMany(Sector::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'events');
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subMonth());
    }

    public function groups()
    {
        return $this->belongsToMany(__CLASS__, 'project_groups', 'group_id');
    }


    public function table()
    {
        return $this->hasOne(Table::class);
    }

    public function moneySources()
    {
        return $this->belongsToMany(MoneySource::class, 'money_source_project');
    }

    public function state()
    {
        return $this->belongsTo(ProjectStates::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
