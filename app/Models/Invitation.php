<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    public function departments() {
        return $this->belongsToMany(Department::class);
    }
}
