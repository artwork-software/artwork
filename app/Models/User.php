<?php

namespace App\Models;

use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Models\HasAvailability;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Notification\Models\GlobalNotification;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ComponentUser;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Models\UserShiftQualification;
use Artwork\Modules\Vacation\Models\GoesOnVacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Artwork\Modules\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon $email_verified_at
 * @property string $phone_number
 * @property string $password
 * @property string $two_factor_secret
 * @property string $two_factor_recovery_codes
 * @property string $position
 * @property string $business
 * @property string $description
 * @property string $toggle_hints
 * @property string $remember_token
 * @property int $current_team_id
 * @property string $profile_photo_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property boolean $project_management
 * @property boolean $can_work_shifts
 * @property string $language
 *
 * @property Collection<\Artwork\Modules\Department\Models\Department> departments
 * @property Collection<\Artwork\Modules\Project\Models\Project> projects
 * @property Collection<\Artwork\Modules\Project\Models\Comment> comments
 * @property Collection<\App\Models\Checklist> private_checklists
 * @property Collection<\Room> created_rooms
 * @property Collection<\Room> admin_rooms
 * @property Collection<\App\Models\Task> done_tasks
 * @property Collection<\Artwork\Modules\Event\Models\Event> events
 * @property Collection<\App\Models\Task> $privateTasks

 *
 * What is this sorcery?
 * @property string $profile_photo_url
 */
class User extends Authenticatable implements Vacationer, Available
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;
    use GoesOnVacation;
    use HasAvailability;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'position',
        'description',
        'toggle_hints',
        'opened_checklists',
        'opened_areas',
        'temporary',
        'employStart',
        'employEnd',
        'can_work_shifts',
        'work_name',
        'work_description',
        'weekly_working_hours',
        'salary_per_hour',
        'salary_description',
        'language',
        'zoom_factor',
        'is_sidebar_opened'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'opened_checklists' => 'array',
        'opened_areas' => 'array',
        'toggle_hints' => 'boolean',
        'temporary' => 'boolean',
        'can_work_shifts' => 'boolean',
        'zoom_factor' => 'float',
        'is_sidebar_opened' => 'boolean'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
        'full_name',
        'type',
        'formatted_vacation_days',
        'assigned_craft_ids'
    ];

    protected $with = ['calendar_settings'];

    public function getTypeAttribute(): string
    {
        return 'user';
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : 'https://ui-avatars.com/api/?name=' .
            urlencode($this->first_name . ' ' . $this->last_name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function crafts(): BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'craft_users');
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_user')
            ->using(ShiftUser::class)
            ->withPivot('id', 'shift_qualification_id');
    }

    public function getShiftsAttribute(): Collection
    {
        return $this->shifts()
            ->without(['craft', 'users', 'event.project.shiftRelevantEventTypes'])
            ->with(['event.room'])
            ->get()
            ->makeHidden(['allUsers']);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    //@todo: fix phpcs error - refactor function name to calendarSettings
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function calendar_settings(): HasOne
    {
        return $this->hasOne(UserCalendarSettings::class);
    }

    public function getFormattedVacationDaysAttribute()
    {
        $vacations = $this->vacations;
        $returnInterval = [];
        foreach ($vacations as $vacation) {
            $start = Carbon::parse($vacation->from);
            $end = Carbon::parse($vacation->until);

            $interval = CarbonPeriod::create($start, $end);

            foreach ($interval as $date) {
                $returnInterval[] = $date->format('Y-m-d');
            }
        }
        return $returnInterval;
    }

    //@todo: fix phpcs error - refactor function name to projectFiles
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function project_files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('access_budget', 'is_manager', 'can_write');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    //@todo: fix phpcs error - refactor function name to privateChecklists
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function private_checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function createdRppms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function adminRooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_user');
    }

    public function doneTasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function privateTasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Checklist::class);
    }

    public function getPermissionAttribute(): \Illuminate\Support\Collection
    {
        return $this->getAllPermissions();
    }

    public function globalNotifications(): HasOne
    {
        return $this->hasOne(GlobalNotification::class, 'created_by');
    }

    //@todo: fix phpcs error - refactor function name to moneySources
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function money_sources(): HasMany
    {
        return $this->hasMany(MoneySource::class, 'creator_id');
    }

    public function moneySourceTasks(): HasMany
    {
        return $this->hasMany(MoneySourceTask::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function accessMoneySources(): BelongsToMany
    {
        return $this->belongsToMany(MoneySource::class, 'money_source_users')
            ->withPivot(['competent', 'write_access'])
            ->using(MoneySourceUserPivot::class);
    }

    //@todo: fix phpcs error - refactor function name to calendarFilter
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function calendar_filter(): HasOne
    {
        return $this->hasOne(UserCalendarFilter::class);
    }

    //@todo: fix phpcs error - refactor function name to shiftCalendarFilter
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function shift_calendar_filter(): HasOne
    {
        return $this->hasOne(UserShiftCalendarFilter::class);
    }

    public function commentedBudgetItemsSetting(): HasOne
    {
        return $this->hasOne(UserCommentedBudgetItemsSetting::class);
    }

    public function assignedCrafts(): BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'users_assigned_crafts');
    }

    public function shiftQualifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(ShiftQualification::class, 'user_shift_qualifications')
            ->using(UserShiftQualification::class);
    }

    /**
     * @return array<int>
     */
    public function getAssignedCraftIdsAttribute(): array
    {
        return $this->assignedCrafts()->pluck('crafts.id')->toArray();
    }

    public function getShiftIdsBetweenStartDateAndEndDate(
        Carbon $startDate,
        Carbon $endDate
    ): \Illuminate\Support\Collection {
        return $this->shifts()->eventStartDayAndEventEndDayBetween($startDate, $endDate)->pluck('shifts.id');
    }

    /**
     * @return string[]
     */
    public function getAllPermissionsAttribute(): array
    {
        $permissions = [];
        foreach (Permission::all() as $permission) {
            if (Auth::user()->can($permission->name)) {
                $permissions[] = $permission->name;
            }
        }
        return $permissions;
    }

    /**
     * @return string[]
     */
    public function getAllRolesAttribute(): array
    {
        $rolesArray = [];
        foreach (Role::all() as $roles) {
            if (Auth::user()->hasRole($roles->name)) {
                $rolesArray[] = $roles->name;
            }
        }
        return $rolesArray;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name
        ];
    }

    public function plannedWorkingHours($startDate, $endDate): float|int
    {
        //dd($startDate, $endDate);

        // get shifts where shift->start_date and shift->end_date is between $startDate and $endDate

        $shiftsInDateRange = $this->shifts()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<', $startDate)
                    ->where('end_date', '>', $endDate);
            })
            ->get();

        $plannedWorkingHours = 0;

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = $shift->start_date->format('Y-m-d') . ' ' . $shift->start; // Parse the start time
            $shiftEnd =  $shift->end_date->format('Y-m-d') . ' ' . $shift->end;    // Parse the end time
            $breakMinutes = $shift->break_minutes;

            $shiftStart = Carbon::parse($shiftStart);
            $shiftEnd = Carbon::parse($shiftEnd);


            $shiftDuration = ($shiftEnd->diffInRealMinutes($shiftStart) - $breakMinutes) / 60;
            $plannedWorkingHours += $shiftDuration;
        }
        return $plannedWorkingHours;
    }

    public function scopeNameOrLastNameLike(Builder $builder, string $name): Builder
    {
        return $builder
            ->where('first_name', 'like', $name . '%')
            ->orWhere('last_name', 'like', $name . '%');
    }
}
