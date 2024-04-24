<?php

namespace Artwork\Modules\Invitation\Services;

use Artwork\Modules\Invitation\Repositories\InvitationRepository;

readonly class InvitationService
{
    public function __construct(private InvitationRepository $invitationRepository)
    {
    }
}
