<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class ContractModule extends Model
{
    use HasFactory;

    protected $guarded = [
        'id'
    ];
}
