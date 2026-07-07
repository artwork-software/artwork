<?php

namespace Artwork\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmPropertyGroupPermission extends Model
{
    protected $fillable = [
        'crm_property_group_id',
        'permissionable_type',
        'permissionable_id',
        'can_view',
        'can_edit',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CrmPropertyGroup::class, 'crm_property_group_id');
    }

    public function permissionable(): MorphTo
    {
        return $this->morphTo();
    }
}
