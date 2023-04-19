<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 */
class ProjectStates extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'name',
        'color'
    ];
}
