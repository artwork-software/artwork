<?php

namespace Artwork\Modules\Permission\Models;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Carbon\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $guard_name
 * @property string|null $name_de
 * @property string|null $tooltipText //why is this camelcase?
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Role extends SpatieRole implements CanSubstituteBaseModel
{

}
