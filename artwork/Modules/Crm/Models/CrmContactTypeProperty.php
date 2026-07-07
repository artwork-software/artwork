<?php

namespace Artwork\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CrmContactTypeProperty extends Pivot
{
    protected $table = 'crm_contact_type_property';

    protected $casts = [
        'is_required' => 'boolean',
        'show_in_list' => 'boolean',
        'is_filterable' => 'boolean',
    ];
}
