<?php

namespace Artwork\Modules\Invitation\Models;

use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property array permissions
 * @property array roles
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
        'roles'
    ];

    protected $casts = [
        'permissions' => 'array',
        'roles' => 'array'
    ];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }
}
