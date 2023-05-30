<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use function Termwind\render;

class Craft extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'abbreviation',
        'assignable_by_all'
    ];

    protected $casts = [
        'assignable_by_all' => 'boolean'
    ];

    protected $with = ['users'];

    public function users(){
        return $this->belongsToMany(User::class, 'craft_users');
    }
}
