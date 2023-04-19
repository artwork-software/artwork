<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesEvents extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency_id',
        'end_date'
    ];


    protected $casts = [
        'end_date' => 'date:Y-m-d',
    ];
}
