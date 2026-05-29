<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
     * Additive scope grant: a tab can only appear once per external access (unique
     * [external_access_id, project_tab_id]). Re-granting the same tab updates the existing
     * scope (project, access type, validity window) instead of failing the unique constraint.
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
                'project_tab_id' => $projectTabId,
            ],
            [
                'project_id' => $projectId,
                'access_type' => $accessType,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'granted_by_user_id' => $grantedByUserId,
            ],
        );

        return $scope;
    }
}
