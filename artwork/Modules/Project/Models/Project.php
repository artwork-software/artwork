<?php

namespace Artwork\Modules\Project\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Models\Category;
use Artwork\Modules\Project\Models\Comment;
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
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Room\Models\Room;
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
 * @property string $description
 * @property string $shift_description
 * @property int $number_of_participants
 * @property string $key_visual_path
 * @property string $num_of_guests
 * @property string $entry_fee
 * @property bool $registration_required
 * @property string $register_by
 * @property string $registration_deadline
 * @property bool $closed_society
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $is_group
 * @property int $state
 * @property string $budget_deadline
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
        'closed_society',
        'budget_deadline',
        'pinned_by_users'
    ];

    protected $casts = [
        'registration_required' => 'boolean',
        'closed_society' => 'boolean',
        'pinned_by_users' => 'array',
    ];

    protected $with = ['shiftRelevantEventTypes', 'state'];


    //@todo: fix phpcs error - refactor function name to costCenter
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function cost_center(): HasOne
    {
        return $this->hasOne(CostCenter::class);
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

    public function copyright(): HasOne
    {
        return $this->hasOne(Copyright::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->withPivot('access_budget', 'is_manager', 'can_write', 'delete_permission');
    }

    public function headlines(): BelongsToMany
    {
        return $this->belongsToMany(ProjectHeadline::class, 'project_project_headlines', 'project_id')
            ->withPivot('text');
    }

    //@todo: fix phpcs error - refactor function name to accessBudget
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function access_budget(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('access_budget', true);
    }

    public function writeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('can_write', true);
    }

    //@todo: fix phpcs error - refactor function name to deletePermissionUsers
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    //@todo: fix phpcs error - refactor function name to projectHistories
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function project_histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
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

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth());
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(ProjectStates::class, 'project_id', 'id', 'state');
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
