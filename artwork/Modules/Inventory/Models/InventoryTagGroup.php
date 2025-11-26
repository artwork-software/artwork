<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTagGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
    ];

    public function tags(): HasMany
    {
        return $this->hasMany(InventoryTag::class, 'inventory_tag_group_id', 'id')
            ->orderBy('position')
            ->orderBy('name');
    }
}
