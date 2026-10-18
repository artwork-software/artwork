<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;

/**
 * Eine Regel für das Vergeben von Tab-Freigaben an Externe – bei der Einladung
 * (StoreExternalInvitationRequest) und beim nachträglichen Ändern einer Freigabe
 * (ExternalAccessManagementController::updateScope):
 * - den Tab muss die vergebende Person selbst sehen dürfen,
 * - Schreibzugriff darf nur vergeben, wer im Projekt schreiben darf (ProjectPolicy::update).
 */
class ExternalScopeGrantGuard
{
    public function __construct(
        private readonly ProjectComponentVisibilityService $visibilityService,
    ) {
    }

    public function maySeeTab(User $user, int $projectTabId): bool
    {
        return $this->visibilityService->canSeeTab($user, $projectTabId);
    }

    public function mayGrantAccessType(User $user, ?Project $project, ExternalAccessType $accessType): bool
    {
        if ($accessType !== ExternalAccessType::WRITE) {
            return true;
        }

        return $project !== null && $user->can('update', $project);
    }
}
