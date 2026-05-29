<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalInvitationRepository extends BaseRepository
{
    public function __construct(private readonly ExternalInvitation $externalInvitation)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->externalInvitation->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->externalInvitation->newModelQuery();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ExternalInvitation
    {
        /** @var ExternalInvitation $invitation */
        $invitation = $this->getNewModelInstance();
        $invitation->fill($attributes);
        $this->save($invitation);

        return $invitation;
    }
}
