<?php

namespace Artwork\Modules\Invitation\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property array permissions
 * @property array roles
 * @property Carbon|null expires_at
 * @property string created_at
 * @property string updated_at
 * @property Collection<Department> $departments
 */
class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'permissions',
        'roles',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'roles' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Ohne Ablaufdatum gilt die Einladung als abgelaufen.
     */
    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }
}
