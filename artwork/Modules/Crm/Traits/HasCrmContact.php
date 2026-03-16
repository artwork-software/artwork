<?php

namespace Artwork\Modules\Crm\Traits;

use Artwork\Modules\Crm\Models\CrmContact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCrmContact
{
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id', 'id', 'crmContact');
    }
}
