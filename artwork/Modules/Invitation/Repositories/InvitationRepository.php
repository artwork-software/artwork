<?php

namespace Artwork\Modules\Invitation\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Invitation\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection as SupportCollection;

class InvitationRepository extends BaseRepository
{
    public function __construct(private readonly Invitation $invitation)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->invitation->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->invitation->newModelQuery();
    }

    public function findByEmail(string $email): ?Invitation
    {
        /** @var Invitation $invitation */
        $invitation = $this->getNewModelQuery()->where('email', $email)->first();

        return $invitation;
    }

    public function findByToken(string $token): ?Invitation
    {
        /** @var Invitation $invitation */
        $invitation = $this->getNewModelQuery()->where('token', $token)->first();

        return $invitation;
    }

    public function syncDepartments(Invitation $invitation, SupportCollection $departments): Invitation
    {
        $invitation->departments()->sync($departments);

        return $invitation;
    }
}
