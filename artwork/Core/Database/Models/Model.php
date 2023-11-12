<?php

namespace Artwork\Core\Database\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Model extends EloquentModel
{
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null): BelongsTo
    {
        if(!$foreignKey || !$ownerKey || !$relation) {
            throw new \InvalidArgumentException('All params of belongsTo have to be used');
        }

        return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
    }
}
