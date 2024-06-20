<?php

namespace Artwork\Modules\Project\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\User\Models\User;
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
 * @property string $description
 * @property string $shift_description
 * @property int $number_of_participants
 * @property string $key_visual_path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $is_group
 * @property int $state
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
        'shift_description',
        'number_of_participants',
        'cost_center_id',
        'key_visual_path',
        'state',
        'budget_deadline',
        'pinned_by_users',
        'own_copyright',
        'live_music',
        'collecting_society_id',
        'law_size',
        'cost_center_description',
    ];

    protected $casts = [
        'pinned_by_users' => 'array',
        'live_music' => 'boolean',
        'own_copyright' => 'boolean',
    ];

    protected $with = [
        'shiftRelevantEventTypes',
        'state'
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id', 'id', 'cost_center');
    }

    public function collectingSociety(): BelongsTo
    {
        return $this->belongsTo(CollectingSociety::class, 'collecting_society_id', 'id', 'collecting_society');
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

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'project_groups', 'group_id');
    }

    public function table(): HasOne
    {
        return $this->hasOne(Table::class);
    }

    public function moneySources(): BelongsToMany
    {
        return $this->belongsToMany(MoneySource::class, 'money_source_project');
    }

    public function state(): HasOne
    {
        return $this->hasOne(
            ProjectStates::class,
            'id',
            'state'
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
        return $builder->where('name', 'like', $query . "%");
    }
}
