<?php

namespace Artwork\Modules\Project\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Contract;
use App\Models\Copyright;
use App\Models\CostCenter;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\MoneySource;
use App\Models\Sector;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
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
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon $deleted_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<User> $users
 * @property \Illuminate\Database\Eloquent\Collection<User> $access_budget
 * @property \Illuminate\Database\Eloquent\Collection<User> $managerUsers
 * @property \Illuminate\Database\Eloquent\Collection<User> $writeUsers
 * @property \Illuminate\Database\Eloquent\Collection<event> $events
 * @property \Illuminate\Database\Eloquent\Collection<Department> $departments
 * @property \Illuminate\Database\Eloquent\Collection<ProjectHistory> $project_histories
 * @property \Illuminate\Database\Eloquent\Collection<Checklist> $checklists
 * @property \Illuminate\Database\Eloquent\Collection<ProjectFile> $project_files
 * @property \Illuminate\Database\Eloquent\Collection<Comment> $comments
 * @property \Illuminate\Database\Eloquent\Collection<Category> $categories
 * @property \Illuminate\Database\Eloquent\Collection<Sector> $sectors
 * @property \Illuminate\Database\Eloquent\Collection<Genre> $genres
 * @property \Illuminate\Database\Eloquent\Collection<Project> $groups
 * @property \Illuminate\Database\Eloquent\Collection<\Room> $rooms
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

    public function shiftRelevantEventTypes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'project_shift_relevant_event_types');
    }

    public function shift_contact(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
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

    public function writeUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('can_write', true);
    }

    public function delete_permission_users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('delete_permission', true);
    }

    public function managerUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
        return $this->belongsToMany(Project::class, 'project_groups', 'group_id');
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
        return $this->belongsTo(ProjectStates::class, 'project_id', 'id', 'state');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
