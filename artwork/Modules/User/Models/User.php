<?php

namespace Artwork\Modules\User\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Models\HasAvailability;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatUser;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Models\Traits\CanHasDayServices;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\IndividualTimes\Models\Traits\HasIndividualTimes;
use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\MoneySource\Models\MoneySourceUserPivot;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\Traits\HasShiftPlanComments;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\UserShiftQualification;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\Traits\HasProfilePhotoCustom;
use Artwork\Modules\User\Services\WorkingHourService;
use Artwork\Modules\Vacation\Models\GoesOnVacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Scout\Searchable;
use LaravelAndVueJS\Traits\LaravelPermissionToVueJS;
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
 * @property int $work_time_balance
 * @property string $profile_photo_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property boolean $project_management
 * @property boolean $can_work_shifts
 * @property string $language
 * @property string $profile_photo_url
 * @property float $zoom_factor
 * @property boolean $is_sidebar_opened
 * @property boolean $compact_mode
 * @property array $show_crafts
 * @property bool $at_a_glance
 * @property Collection<Department> $departments
 * @property Collection<Project> $projects
 * @property Collection<Comment> $comments
 * @property Collection<Checklist> $private_checklists
 * @property Collection<Room> $created_rooms
 * @property Collection<Room> $admin_rooms
 * @property Collection<Task> $done_tasks
 * @property Collection<Task> $privateTasks
 * @property Collection<Event> $events
 * @property Collection<Craft> $crafts
 * @property UserShiftCalendarFilter $shift_calendar_filter
 * @property UserCalendarFilter $calendar_filter
 * @property UserCalendarSettings $calendar_settings
 * @property Collection<Shift> $shifts
 * @property Collection<Permission> $permission
 * @property Collection<Role> $allRoles
 * @property Collection<MoneySource> $money_sources
 * @property Collection<MoneySourceTask> $moneySourceTasks
 * @property Collection<Task> $tasks
 * @property Collection<MoneySource> $accessMoneySources
 * @property Collection<ShiftQualification> $shiftQualifications
 * @property Collection<Craft> $assignedCrafts
 * @property Collection<Shift> $shiftIdsBetweenStartDateAndEndDate
 * @property Collection<UserCalendarAbo> $calendarAbo
 * @property Collection<UserShiftCalendarAbo> $shiftCalendarAbo
 * @property Collection<UserWorkTime> $workTime
 * @property Collection<UserContractAssign> $contract
 * @property Collection<string> $allPermissions
 * @property array $notification_enums_last_sent_dates
 * @property int $bulk_sort_id
 * @property boolean $show_notification_indicator
 * @property int $shift_plan_user_sort_by_id
 * @property boolean $is_freelancer
 * @property string $sort_type_shift_tab
 * @property int $drawer_height
 * @property int $inventory_sort_column_id
 * @property int $inventory_sort_direction
 * @property boolean $checklist_has_projects
 * @property boolean $checklist_no_projects
 * @property boolean $checklist_private_checklists
 * @property boolean $checklist_no_private_checklists
 * @property boolean $checklist_completed_tasks
 * @property boolean $checklist_show_without_tasks
 * @property boolean $is_developer
 * @property array $show_qualifications
 * @property boolean $email_private
 * @property boolean $phone_private
 * @property boolean $daily_view
 * @property int $last_project_id
 * @property array $bulk_column_size
 * @property string $chat_public_key
 * @property boolean $use_chat
 * @property string $work_name
 * @property string $work_description
 * @property int $weekly_working_hours
 * @property float $salary_per_hour
 * @property string $salary_description
 * @property Collection<GlobalQualification> $globalQualifications
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    Vacationer,
    Available,
    DayServiceable
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use MustVerifyEmail;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    //use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;
    use GoesOnVacation;
    use HasAvailability;
    use CanHasDayServices;
    use HasIndividualTimes;
    use HasShiftPlanComments;
    use LaravelPermissionToVueJS;
    use HasProfilePhotoCustom;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'position',
        'pronouns',
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
        'is_sidebar_opened',
        'compact_mode',
        'show_crafts',
        'goto_mode',
        'checklist_style',
        'at_a_glance',
        'notification_enums_last_sent_dates',
        'bulk_sort_id',
        'show_notification_indicator',
        'shift_plan_user_sort_by_id',
        'is_freelancer',
        'sort_type_shift_tab',
        'drawer_height',
        'inventory_sort_column_id',
        'inventory_sort_direction',
        'checklist_has_projects',
        'checklist_no_projects',
        'checklist_private_checklists',
        'checklist_no_private_checklists',
        'checklist_completed_tasks',
        'checklist_show_without_tasks',
        'is_developer',
        'show_qualifications',
        'email_private',
        'phone_private',
        'daily_view',
        'entities_per_page',
        'last_project_id',
        'bulk_column_size',
        'chat_public_key',
        'use_chat',
        'work_time_balance',
        'chat_popup_position',
        'chat_push_notification',
        'is_time_preset_open'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'opened_checklists' => 'array',
        'opened_areas' => 'array',
        'toggle_hints' => 'boolean',
        'temporary' => 'boolean',
        'can_work_shifts' => 'boolean',
        'zoom_factor' => 'float',
        'is_sidebar_opened' => 'boolean',
        'compact_mode' => 'boolean',
        'show_crafts' => 'array',
        'at_a_glance' => 'boolean',
        'notification_enums_last_sent_dates' => 'array',
        'show_notification_indicator' => 'boolean',
        'is_freelancer' => 'boolean',
        'checklist_has_projects' => 'boolean',
        'checklist_no_projects' => 'boolean',
        'checklist_private_checklists' => 'boolean',
        'checklist_no_private_checklists' => 'boolean',
        'checklist_completed_tasks' => 'boolean',
        'checklist_show_without_tasks' => 'boolean',
        'is_developer' => 'boolean',
        'show_qualifications' => 'array',
        'email_private' => 'boolean',
        'phone_private' => 'boolean',
        'daily_view' => 'boolean',
        'bulk_column_size' => 'array',
        'use_chat' => 'boolean',
        'chat_push_notification' => 'boolean',
        'is_time_preset_open' => 'boolean',
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
        'formated_work_time_balance',
        //'assigned_craft_ids',
    ];

    public function globalQualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalQualification::class,
            'user_global_qualifications',
            'user_id',
            'global_qualification_id'
        );
    }

    //protected $with = ['calendarAbo', 'shiftCalendarAbo'];

        /**
         * Beziehung zum InventoryUserFilter
         */
        public function inventoryUserFilter()
        {
            return $this->hasOne(\Artwork\Modules\Inventory\Models\InventoryUserFilter::class, 'user_id');
        }

    public function getTypeAttribute(): string
    {
        return 'user';
    }

    public function productBasket(): HasMany
    {
        return $this->hasMany(ProductBasket::class, 'user_id', 'id');
    }

    public function shiftCalendarAbo(): hasOne
    {
        return $this->hasOne(UserShiftCalendarAbo::class);
    }

    public function calendarAbo(): hasOne
    {
        return $this->hasOne(UserCalendarAbo::class);
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : route('generate-avatar-image', ['letters' => $this->first_name[0] . $this->last_name[0]]);
    }


    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_user')
            ->using(ShiftUser::class)
            ->withPivot([
                'id',
                'shift_qualification_id',
                'shift_count',
                'craft_abbreviation',
                'short_description',
                'start_date',
                'end_date',
                'start_time',
                'end_time'
            ]);
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

    /**
     * @return array<string>
     */
    public function getFormattedVacationDays(): array
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
        return $this->belongsToMany(Project::class)
            ->withPivot('access_budget', 'is_manager', 'can_write');
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

    public function createdRooms(): HasMany
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

    public function globalNotification(): HasOne
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

    /**
     * New Filter for Calendar
     */
    public function userFilters(): HasMany
    {
        return $this->hasMany(UserFilter::class, 'user_id', 'id');
    }

    public function userFilterTemplates(): HasMany
    {
        return $this->hasMany(UserFilterTemplate::class, 'user_id', 'id');
    }

    public function commentedBudgetItemsSetting(): HasOne
    {
        return $this->hasOne(UserCommentedBudgetItemsSetting::class);
    }

    public function crafts(): BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'craft_users');
    }

    public function assignedCrafts(): morphToMany
    {
        return $this->morphToMany(Craft::class, 'craftable')->with(['qualifications']);
    }

    public function managingCrafts(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    public function shiftQualifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(ShiftQualification::class, 'user_shift_qualifications')
            ->using(UserShiftQualification::class);
    }

    public function workerShiftPlanFilter(): HasOne
    {
        return $this->hasOne(UserWorkerShiftPlanFilter::class);
    }

    public function inventoryArticlePlanFilter(): HasOne
    {
        return $this->hasOne(UserInventoryArticlePlanFilter::class);
    }

    /**
     * @return array<int>
     */
    public function getAssignedCraftIdsAttribute(): array
    {
        return $this->assignedCrafts()->pluck('crafts.id')->all();
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
    public function allPermissions(): array
    {
        if (!$this->exists) {
            return [];
        }
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * @return string[]
     */
    public function allRoles(): array
    {
        if (!$this->exists) {
            return [];
        }

        return $this->roles()->pluck('name')->toArray();
    }


    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'profile_photo_path' => $this->profile_photo_path,
            'position' => $this->position,
            'business' => $this->business,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'chat_public_key' => $this->chat_public_key,
            'use_chat' => $this->use_chat,
        ];
    }

    /** @deprecated user WorkhourService */
    public function plannedWorkingHours($startDate, $endDate): float|int
    {
        trigger_deprecation(
            'artwork',
            '0.x',
            'User::plannedWorkingHours() is deprecated. Use WorkhourService instead.'
        );
        return app(WorkingHourService::class)->plannedWorkingHoursForUser($this, $startDate, $endDate) / 60;
    }

    public function scopeNameOrLastNameLike(Builder $builder, string $name): Builder
    {
        return $builder
            ->where('first_name', 'like', $name . '%')
            ->orWhere('last_name', 'like', $name . '%');
    }

    public function scopeCanWorkShifts(Builder $builder): Builder
    {
        return $builder->where('can_work_shifts', true);
    }

    public function getHasProjectManagerPermission(): bool
    {
        return $this->hasPermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value)
            || $this->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    public function inventoryManagementFilter(): HasOne
    {
        return $this->hasOne(
            InventoryManagementUserFilter::class,
            'user_id',
            'id'
        );
    }

    public function projectFilterAndSortSetting(): HasOne
    {
        return $this->hasOne(
            UserUserManagementSetting::class,
            'user_id',
            'id'
        );
    }

    public function userFilterAndSortSetting(): HasOne
    {
        return $this->hasOne(
            UserUserManagementSetting::class,
            'user_id',
            'id'
        );
    }

    public function craftsToManage(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    /**
     * @return array<int, int>
     */
    public function getManagingCraftIds(): array
    {
        return $this->craftsToManage()->pluck('id')->toArray();
    }

    public function lastProject(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'last_project_id');
    }

    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_users')
            ->using(ChatUser::class);
    }

    public function verifiableEventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'event_type_user');
    }

    public function eventVerifications(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(EventVerification::class, 'verifier');
    }

    public function workTimes(): HasMany
    {
        return $this->hasMany(UserWorkTime::class, 'user_id', 'id');
    }

    public function getNextWorkTime(): ?UserWorkTime
    {
        return $this->workTimes()
            ->whereDate('valid_from', '>', now())
            ->orderBy('valid_from')
            ->first();
    }

    public function getCurrentWorkTime(): ?UserWorkTime
    {
        return $this->workTimes()
            ->where('is_active', true)
            ->first();
    }

    public function contract(): HasOne
    {
        return $this->hasOne(UserContractAssign::class, 'user_id', 'id');
    }

    public function workTimeBookings(): HasMany
    {
        return $this->hasMany(WorkTimeBooking::class, 'user_id', 'id');
    }

    public function getFormatedWorkTimeBalanceAttribute(): string
    {
        // convert work_time_balance to hours and minutes
        $hours = floor($this->work_time_balance / 60);
        $minutes = $this->work_time_balance % 60;

        // format as "HH:MM"
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Exclude the placeholder "Deleted user" from Scout indexing
     */
    public function shouldBeSearchable(): bool
    {
        return $this->email !== config('artwork.deleted_user_email', 'deleted-user@artwork.local');
    }

    /**
     * Convenience scope to hide the placeholder user in queries
     */
    public function scopeExcludeDeletedPlaceholder(Builder $builder): Builder
    {
        return $builder->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'));
    }

}
