<?php

namespace Artwork\Modules\CostCenter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class CostCenter extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
      'name',
    ];
}
