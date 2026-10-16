<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Project\Services\ProjectArtistService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Ein CRM-Kontakt in einer Komponente „CRM-Kontaktliste“ eines Projekts.
 *
 * @property int $id
 * @property int $project_id
 * @property int $component_id
 * @property int $crm_contact_id
 * @property list<string>|null $roles Freitext-Rollen in dieser Liste
 * @property bool $is_artist Sonderrolle „Künstler*in“ (= Projektkünstler*in)
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
        'roles',
        'is_artist',
        'created_by_user_id',
        'created_by_external_access_id',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'roles' => 'array',
            'is_artist' => 'boolean',
        ];
    }

    /**
     * Sonderrolle „Künstler*in“ ändert die Projektkünstler*innen (projects.linked_artists): nach jedem
     * Anlegen/Ändern/Entfernen neu berechnen – nach dem Commit, damit die Liste den gespeicherten Stand zeigt.
     */
    protected static function booted(): void
    {
        $refresh = static function (self $entry): void {
            $projectId = (int) $entry->project_id;
            DB::afterCommit(static fn () => app(ProjectArtistService::class)->refresh($projectId));
        };

        static::created(fn (self $entry) => $entry->is_artist ? $refresh($entry) : null);
        static::updated(fn (self $entry) => $entry->wasChanged(['is_artist', 'crm_contact_id', 'project_id'])
            ? $refresh($entry)
            : null);
        static::deleted(fn (self $entry) => $entry->is_artist ? $refresh($entry) : null);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id', 'id', 'crmContact');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id', 'createdByUser');
    }

    /**
     * @return BelongsTo<ExternalAccess, $this>
     */
    public function createdByExternalAccess(): BelongsTo
    {
        return $this->belongsTo(
            ExternalAccess::class,
            'created_by_external_access_id',
            'id',
            'createdByExternalAccess'
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'id', 'reviewedBy');
    }

    public function isCreatedByExternal(ExternalAccess $external): bool
    {
        return (int) $this->created_by_external_access_id === (int) $external->id;
    }
}
