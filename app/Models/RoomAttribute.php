<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }
}
