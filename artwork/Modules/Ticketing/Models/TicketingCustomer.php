<?php

namespace Artwork\Modules\Ticketing\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Crm\Models\CrmContact;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Käufer aus Artwork-Tickets, gespiegelt in einen CRM-Kontakt des Typs "Ticketing-Kunde".
 *
 * @property int $id
 * @property string $customer_id
 * @property int $crm_contact_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CrmContact $crmContact
 */
class TicketingCustomer extends Model
{
    protected $table = 'ticketing_customers';

    protected $fillable = [
        'customer_id',
        'crm_contact_id',
    ];

    /** @return BelongsTo<CrmContact, $this> */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id', 'id', 'crmContact');
    }
}
