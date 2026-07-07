<?php

namespace Artwork\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPropertyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'color',
        'is_confidential',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_confidential' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(CrmProperty::class, 'crm_property_group_id')
            ->orderBy('sort_order');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(CrmPropertyGroupPermission::class, 'crm_property_group_id');
    }
}
