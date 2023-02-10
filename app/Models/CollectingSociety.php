<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectingSociety extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function copyrights()
    {
        return $this->belongsToMany(Copyright::class);
    }
}
