<?php

namespace Artwork\Modules\ContractModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

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
