<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scheduling extends Model
{
    use HasFactory;

    protected $fillable = [
        'count',
        'user_id',
        'type',
        'model',
        'model_id',
    ];
}

