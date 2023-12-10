<?php

namespace App\Models;

use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property array permissions
 * @property array roles
 * @property string created_at
 * @property string updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<\Artwork\Modules\Department\Models\Department> $departments
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

    public function departments() {
        return $this->belongsToMany(Department::class);
    }
}
