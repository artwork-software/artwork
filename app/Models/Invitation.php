<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property string permissions
 * @property string role
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
        'role'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    public function departments() {
        return $this->belongsToMany(Department::class);
    }
}
