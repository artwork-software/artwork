<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalAccessRepository extends BaseRepository
{
    public function __construct(private readonly ExternalAccess $externalAccess)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->externalAccess->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->externalAccess->newModelQuery();
    }

    public function findByEmail(string $email): ?ExternalAccess
    {
        /** @var ExternalAccess|null $external */
        $external = $this->getNewModelQuery()->where('email', $email)->first();

        return $external;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ExternalAccess
    {
        /** @var ExternalAccess $external */
        $external = $this->getNewModelInstance();
        $external->fill($attributes);
        $this->save($external);

        return $external;
    }

    /**
     * All external accesses for a contact, including revoked/expired (management overview).
     *
     * @return Collection<int, ExternalAccess>
     */
    public function findAllForContact(CrmContact $contact): Collection
    {
        return $this->getNewModelQuery()
            ->where('crm_contact_id', $contact->id)
            ->with(['invitedBy:id,first_name,last_name', 'scopes.project:id,name', 'scopes.projectTab:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Global, paginated overview, optionally filtered by status (active|revoked|expired).
     */
    public function findGlobalIndex(?string $statusFilter = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->getNewModelQuery()
            ->with(['crmContact:id,display_name,entity_type,entity_id', 'invitedBy:id,first_name,last_name'])
            ->withCount(['scopes', 'pendingSubmissions']);

        match ($statusFilter) {
            'active' => $query->whereNull('revoked_at')
                ->where(fn (Builder $q) => $q->where('crm_access_expires_at', '>', now())
                    ->orWhereNull('crm_access_expires_at')),
            'revoked' => $query->whereNotNull('revoked_at'),
            'expired' => $query->whereNull('revoked_at')
                ->whereNotNull('crm_access_expires_at')
                ->where('crm_access_expires_at', '<=', now()),
            default => null,
        };

        return $query->orderByDesc('last_login_at')->paginate($perPage);
    }
}
