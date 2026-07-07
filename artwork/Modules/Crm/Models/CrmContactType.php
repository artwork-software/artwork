<?php

namespace Artwork\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContactType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'crm_contact_type_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmProperty::class,
            'crm_contact_type_property',
            'crm_contact_type_id',
            'crm_property_id'
        )->using(CrmContactTypeProperty::class)
         ->withPivot(['sort_order', 'is_required', 'show_in_list', 'is_filterable'])
         ->orderByPivot('sort_order');
    }
}
