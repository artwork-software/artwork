<?php

namespace Artwork\Modules\Crm\Models;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'crm_property_group_id',
        'name',
        'type',
        'select_values',
        'tooltip_text',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'type' => CrmPropertyTypeEnum::class,
        'select_values' => 'array',
        'is_system' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CrmPropertyGroup::class, 'crm_property_group_id');
    }

    public function contactTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmContactType::class,
            'crm_contact_type_property',
            'crm_property_id',
            'crm_contact_type_id'
        )->using(CrmContactTypeProperty::class)
         ->withPivot(['sort_order', 'is_required', 'show_in_list', 'is_filterable']);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CrmPropertyValue::class, 'crm_property_id');
    }
}
