<?php

namespace App\Models;

use  Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

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
        'business',
        'description',
        'toggle_hints',
        'opened_checklists',
        'opened_areas'
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withPivot('is_admin', 'is_manager');;
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

    public function admin_rooms() {
        return $this->belongsToMany(Room::class, 'room_user');
    }

    public function done_tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function events() {
        return $this->hasMany(event::class);
    }

    public function getPermissionAttribute()
    {
        return $this->getAllPermissions();
    }

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'opened_checklists' => 'array',
        'opened_areas' => 'array'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected $with =[
        'permissions',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name
        ];

    }
}
