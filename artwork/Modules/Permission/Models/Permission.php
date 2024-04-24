<?php

namespace Artwork\Modules\Permission\Models;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use BadMethodCallException;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $guard_name
 * @property string|null $name_de
 * @property string|null $group
 * @property string|null $tooltipText //why is this camelcase?
 * @property bool $checked
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Permission extends SpatiePermission implements CanSubstituteBaseModel
{
    public function restore(): bool
    {
        throw new BadMethodCallException(
            'Implement SoftDeletes-Trait in "' . __CLASS__ . '" to use this function.'
        );
    }

    public function restoreQuietly(): bool
    {
        throw new BadMethodCallException(
            'Implement SoftDeletes-Trait in "' . __CLASS__ . '" to use this function.'
        );
    }
}
