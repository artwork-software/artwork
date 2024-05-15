<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 */
class ProjectRole extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
