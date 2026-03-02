<?php

namespace Artwork\Modules\Project\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property bool $gema
 * @property string $cost_center_description
 * @property string $artists
 * @property string $description
 * @property string $shift_description
 * @property int $number_of_participants
 * @property string $key_visual_path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $is_group
 * @property ProjectState $state
 * @property string $budget_deadline
 * @property Table|null $table
 * @property Collection<User> $managerUsers
 * @property Collection<ProjectFile> $project_files
 * @property Collection<MoneySource> $moneySources
 * @property Collection<User> $access_budget
 * @property Collection<Contract> $contracts
 * @property Collection<EventType> $shiftRelevantEventTypes
 * @property Collection<Event> $events
 * @property Collection<Department> $departments
 * @property Collection<Checklist> $checklists
 * @property Collection<User> $shift_contact
 * @property Collection<User> $users
 * @property Collection<User> $writeUsers
 * @property Collection<User> $delete_permission_users
 * @property Collection<Sector> $sectors
 * @property Collection<Category> $categories
 * @property Collection<Genre> $genres
 * @property Collection<Room> $rooms
 * @property CostCenter $costCenter
 * @property Collection<Project> $groups
 * @property Collection<Project> $projectsOfGroup
 * @property Collection<Comment> $comments
 * @property Collection<ArtistResidency> $artistResidencies
 * @property Collection<ProjectState> $status
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;
    use Searchable;
    use HasChangesHistory;

    protected $fillable = [
        'name',
        'artists',
        'shift_description',
        'number_of_participants',
        'cost_center_id',
        'key_visual_path',
        'state',
        'budget_deadline',
        'pinned_by_users',
        'gema',
        'cost_center_description',
        'is_group',
        'user_id',
        'color',
        'icon',
        'marked_as_done'
    ];

    protected $casts = [
        'pinned_by_users' => 'array',
        'gema' => 'boolean',
        'is_group' => 'boolean',
        'marked_as_done' => 'boolean',
    ];

    protected $appends = [
    ];

    protected $with = [
        //'shiftRelevantEventTypes',
        //'status'
    ];

    public static function booting(): void
    {
        static::softDeleted(function ($project): void {
            UserCalendarSettings::query()->where('time_period_project_id', $project->id)->update(
                ['time_period_project_id' => 0, 'use_project_time_period' => 0]
            );
        });

        static::deleting(function ($project): void {
            // Delete all artist residencies before deleting the project
            $project->artistResidencies()->delete();
        });

        static::deleted(function ($project): void {
            UserCalendarSettings::query()->where('time_period_project_id', $project->id)->update(
                ['time_period_project_id' => 0, 'use_project_time_period' => 0]
            );
        });
    }

    public function artistResidencies(): HasMany
    {
        return $this->hasMany(ArtistResidency::class, 'project_id', 'id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id', 'id', 'cost_center');
    }


    public function shiftRelevantEventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'project_shift_relevant_event_types');
    }

    //@todo: fix phpcs error - refactor function name to shiftContact
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function shift_contact(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_shift_contacts');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->using(ProjectUserPivot::class)
            ->withPivot('access_budget', 'is_manager', 'can_write', 'delete_permission', 'roles')
            ->without(['vacation', 'calender_settings']);
    }

    //@todo: fix phpcs error - refactor function name to accessBudget
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function access_budget(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('access_budget', true)->withCasts(['access_budget' => 'boolean'])
            ->without(['vacation', 'calender_settings']);
    }

    public function writeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('can_write', true)
            ->without(['vacation', 'calender_settings']);
    }

    //@todo: fix phpcs error - refactor function name to deletePermissionUsers
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function delete_permission_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('delete_permission', true)
            ->without(['vacation', 'calender_settings']);
    }

    public function managerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('is_manager', true)
            ->without(['vacation', 'calender_settings']);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class)->with(['tasks', 'company_type', 'contract_type', 'currency']);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    //@todo: fix phpcs error - refactor function name to projectFiles
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function project_files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'project_id', 'id');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'events');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'project_id', 'id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'project_groups', 'project_id', 'group_id', 'id')->with(['users']);
    }

    public function projectsOfGroup(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'project_groups', 'group_id', 'project_id', 'id')->with(['users']);
    }

    public function table(): HasOne
    {
        return $this->hasOne(Table::class);
    }

    public function moneySources(): BelongsToMany
    {
        return $this->belongsToMany(MoneySource::class, 'money_source_project');
    }

    public function status(): HasOne
    {
        return $this->hasOne(
            ProjectState::class,
            'id',
            'state',
        );
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed();
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

    public function getFirstAndLastEventDateAttribute(): ?array
    {
        $firstEvent = $this->events()->orderBy('start_time', 'ASC')->first();
        $lastEvent = $this->events()->orderBy('end_time', 'DESC')->first();

        if ($firstEvent && $lastEvent) {
            return [
                'first_event_date' => Carbon::parse($firstEvent->start_time)->translatedFormat('d.m.Y H:i'),
                'last_event_date' => Carbon::parse($lastEvent->end_time)->translatedFormat('d.m.Y H:i'),
            ];
        }

        return null;
    }

    public function scopeWhereNotDeleted(Builder $builder): Builder
    {
        return $builder->whereNull('deleted_at');
    }

    public function scopeByCostCenter(Builder $builder, string $costCenter): Builder
    {
        return $builder->whereRelation('costCenter', 'name', $costCenter);
    }

    public function scopeByName(Builder $builder, string $query): Builder
    {
        return $builder->where('name', 'like', "%" . $query . "%");
    }
}
