<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $count
 * @property int $user_id
 * @property string $type
 * @property int $model_id
 * @property string $created_at
 * @property string $updated_at
 */
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
