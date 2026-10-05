<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalAccessScopeRepository extends BaseRepository
{
    public function __construct(private readonly ExternalAccessScope $externalAccessScope)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->externalAccessScope->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->externalAccessScope->newModelQuery();
    }

    /**
     * Additive scope grant: a tab can only appear once per external access AND project (unique
     * [external_access_id, project_id, project_tab_id]). Re-granting the same tab in the same
     * project updates the existing scope (access type, validity window); the same tab in another
     * project creates a second, independent scope.
     */
    public function addOrUpdateScope(
        ExternalAccess $externalAccess,
        int $projectId,
        int $projectTabId,
        ExternalAccessType $accessType,
        CarbonInterface $validFrom,
        CarbonInterface $validTo,
        ?int $grantedByUserId,
    ): ExternalAccessScope {
        /** @var ExternalAccessScope $scope */
        $scope = $this->getNewModelQuery()->updateOrCreate(
            [
                'external_access_id' => $externalAccess->id,
                'project_id' => $projectId,
                'project_tab_id' => $projectTabId,
            ],
            [
                'access_type' => $accessType,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'granted_by_user_id' => $grantedByUserId,
                // erneute Einladung = neue Laufzeit → Ablauf-Erinnerung erneut möglich
                'expiry_reminder_sent_at' => null,
            ],
        );

        // Erneute Einladung in einen abgesendeten/bestätigten Tab: wieder zum Ausfüllen freigeben,
        // sonst käme die Person per Einladungsmail in einen gesperrten Tab.
        if ($scope->isLockedForExternal() && $accessType === ExternalAccessType::WRITE) {
            $scope->forceFill(['submission_status' => ExternalTabSubmissionStatus::OPEN])->save();
        }

        return $scope;
    }

    /**
     * All scopes of an access (active and past), newest validity first.
     *
     * @return Collection<int, ExternalAccessScope>
     */
    public function findByAccess(ExternalAccess $access): Collection
    {
        return $access->scopes()
            ->with(['project:id,name', 'projectTab:id,name', 'grantedBy:id,first_name,last_name'])
            ->orderByDesc('valid_to')
            ->get();
    }
}
