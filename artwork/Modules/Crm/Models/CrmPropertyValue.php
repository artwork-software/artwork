<?php

namespace Artwork\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmPropertyValue extends Model
{
    protected $fillable = [
        'crm_contact_id',
        'crm_property_id',
        'value',
    ];

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    /**
     * @return BelongsTo<CrmProperty, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(CrmProperty::class, 'crm_property_id');
    }
}
