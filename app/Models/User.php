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
        'toggle_hints'
    ];

    public function departments() {
        return $this->belongsToMany(Department::class);
    }

    public function projects() {
        return $this->belongsToMany(Project::class)->withPivot('is_admin', 'is_manager');;
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
        'smallLogo',
        'bigLogo',
        'banner'
    ];

    public function getBannerAttribute(): ?string
    {
        $path = app(GeneralSettings::class)->banner_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }

    public function getSmallLogoAttribute(): ?string
    {
        $path = app(GeneralSettings::class)->small_logo_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }

    public function getBigLogoAttribute(): ?string
    {
        $path = app(GeneralSettings::class)->big_logo_path;

        if($path) {
            return Storage::disk('public')->url($path);
        } else {
            return null;
        }

    }
}
