<?php

namespace App\Models;

use App\Http\Resources\CalendarEventResource;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 *
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
 * @property boolean $can_master
 * @property boolean $can_work_shifts
 *
 * @property Collection<\App\Models\Department> departments
 * @property Collection<\App\Models\Project> projects
 * @property Collection<\App\Models\Comment> comments
 * @property Collection<\App\Models\Checklist> private_checklists
 * @property Collection<\App\Models\Room> created_rooms
 * @property Collection<\App\Models\Room> admin_rooms
 * @property Collection<\App\Models\Task> done_tasks
 * @property Collection<\App\Models\Event> events
 * @property Collection<\App\Models\Task> $privateTasks
 *
 * What is this sorcery?
 * @property string $profile_photo_url
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasPermissions;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
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
        'can_master',
        'can_work_shifts',
        'work_name',
        'work_description',
        'weekly_working_hours',
        'salary_per_hour',
        'salary_description',
    ];



    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'opened_checklists' => 'array',
        'opened_areas' => 'array',
        'toggle_hints' => 'boolean',
        'temporary' => 'boolean',
        'can_master' => 'boolean',
        'can_work_shifts' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
        'full_name',
        'type',
    ];

    protected $with = ['calendar_settings'];


    public function getTypeAttribute(): string
    {
        return 'user';
    }

    public function crafts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'craft_users');
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_user', 'user_id', 'shift_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean']);
    }

    public function getShiftsAttribute(): Collection
    {
        return $this->shifts()
            ->without(['craft', 'users', 'event.project.shiftRelevantEventTypes'])
            ->with(['event.room'])
            ->get()
            ->makeHidden(['allUsers'])
            ->groupBy(function ($shift) {
                return $shift->event->days_of_event;
            });
    }

    public function getFullNameAttribute(){
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    public function calendar_settings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserCalendarSettings::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(UserVacations::class);
    }

    public function project_files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function notificationSettings(): HasMany {
        return $this->hasMany(NotificationSetting::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withPivot('access_budget', 'is_manager', 'can_write');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function private_checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function created_rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function admin_rooms()
    {
        return $this->belongsToMany(Room::class, 'room_user');
    }

    public function done_tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
    public function privateTasks()
    {
        return $this->hasManyThrough(Task::class, Checklist::class);
    }

    public function getPermissionAttribute()
    {
        return $this->getAllPermissions();
    }

    public function globalNotifications()
    {
        return $this->hasOne(GlobalNotification::class, 'created_by');
    }

    public function money_sources(){
        return $this->hasMany(MoneySource::class, 'creator_id');
    }

    public function money_source_tasks(): HasMany
    {
        return $this->hasMany(MoneySourceTask::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function accessMoneySources(){
        return $this->belongsToMany(MoneySource::class, 'money_source_users')->withPivot(['competent', 'write_access'])->using(MoneySourceUserPivot::class);
    }


    public function calendar_filter(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserCalendarFilter::class);
    }

    public function shift_calendar_filter(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserShiftCalendarFilter::class);
    }

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
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name
        ];
    }
    public function plannedWorkingHours($startDate, $endDate): float|int
    {
        $shiftsInDateRange = $this->shifts()
            ->whereBetween('event_start_day', [$startDate, $endDate])
            ->get();

        $plannedWorkingHours = 0;

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = Carbon::parse($shift->start); // Parse the start time
            $shiftEnd = Carbon::parse($shift->end);     // Parse the end time
            $breakMinutes = $shift->break_minutes;

            $shiftDuration = ($shiftEnd->diffInRealMinutes($shiftStart) - $breakMinutes) / 60;
            $plannedWorkingHours += $shiftDuration;
        }

        return $plannedWorkingHours;
    }

    public function hasVacationDays(){
        $vacations = $this->vacations()->get();
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


    public function hasVacation(){
        $vacations = $this->vacations()->get();
        $returnInterval = [];
        foreach ($vacations as $vacation) {
            $start = Carbon::parse($vacation->from);
            $end = Carbon::parse($vacation->until);

            $interval = CarbonPeriod::create($start, $end);

            foreach ($interval as $date) {
                $returnInterval[$date->format('d.m.Y')] = $date->format('Y-m-d');
            }
        }
        return $returnInterval;
    }
}
