<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property string permissions
 * @property string roles
 * @property string created_at
 * @property string updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Department> $departments
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
