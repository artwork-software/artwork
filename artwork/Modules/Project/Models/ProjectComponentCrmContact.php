<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein CRM-Kontakt in einer Komponente „CRM-Kontaktliste“ eines Projekts.
 *
 * @property int $id
 * @property int $project_id
 * @property int $component_id
 * @property int $crm_contact_id
 * @property int|null $created_by_user_id
 * @property int|null $created_by_external_access_id
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CrmContact|null $crmContact
 * @property-read ExternalAccess|null $createdByExternalAccess
 * @property-read User|null $createdByUser
 * @property-read User|null $reviewedBy
 */
class ProjectComponentCrmContact extends Model
{
    protected $table = 'project_component_crm_contacts';

    protected $fillable = [
        'project_id',
        'component_id',
        'crm_contact_id',
        'created_by_user_id',
        'created_by_external_access_id',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }

    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id', 'id', 'crmContact');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id', 'createdByUser');
    }

    public function createdByExternalAccess(): BelongsTo
    {
        return $this->belongsTo(
            ExternalAccess::class,
            'created_by_external_access_id',
            'id',
            'createdByExternalAccess'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'id', 'reviewedBy');
    }

    public function isCreatedByExternal(ExternalAccess $external): bool
    {
        return (int) $this->created_by_external_access_id === (int) $external->id;
    }
}
