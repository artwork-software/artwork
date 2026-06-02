<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\DatabaseManager;

class ExternalAccessManagementService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    public function extendCrmAccess(ExternalAccess $access, CarbonInterface $newExpiry, User $actor): void
    {
        if ($newExpiry->isPast()) {
            throw new DomainException('Expiry must be in the future.');
        }

        $oldExpiry = $access->crm_access_expires_at;
        $access->update(['crm_access_expires_at' => $newExpiry]);

        activity('external_access_management')
            ->performedOn($access)
            ->causedBy($actor)
            ->withProperties([
                'old_expiry' => $oldExpiry?->toIso8601String(),
                'new_expiry' => $newExpiry->toIso8601String(),
            ])
            ->log('crm_access_extended');
    }

    public function revoke(ExternalAccess $access, User $actor, ?string $reason = null): void
    {
        if ($access->revoked_at !== null) {
            return; // idempotent
        }

        $access->update(['revoked_at' => now()]);

        activity('external_access_management')
            ->performedOn($access)
            ->causedBy($actor)
            ->withProperties(['reason' => $reason])
            ->log('access_revoked');
    }

    public function reactivate(ExternalAccess $access, User $actor): void
    {
        if ($access->revoked_at === null) {
            return;
        }

        $access->update(['revoked_at' => null]);

        activity('external_access_management')
            ->performedOn($access)
            ->causedBy($actor)
            ->log('access_reactivated');
    }

    public function extendScope(ExternalAccessScope $scope, CarbonInterface $newValidTo, User $actor): void
    {
        if ($newValidTo->isBefore($scope->valid_from)) {
            throw new DomainException('New valid_to must be after valid_from.');
        }

        $oldValidTo = $scope->valid_to;
        $scope->update(['valid_to' => $newValidTo]);

        activity('external_access_management')
            ->performedOn($scope)
            ->causedBy($actor)
            ->withProperties([
                'old_valid_to' => $oldValidTo->toIso8601String(),
                'new_valid_to' => $newValidTo->toIso8601String(),
            ])
            ->log('scope_extended');
    }

    public function endScope(ExternalAccessScope $scope, User $actor): void
    {
        $scope->update(['valid_to' => now()]);

        activity('external_access_management')
            ->performedOn($scope)
            ->causedBy($actor)
            ->log('scope_ended');
    }

    public function updateScopeAccessType(ExternalAccessScope $scope, ExternalAccessType $newType, User $actor): void
    {
        if ($scope->access_type === $newType) {
            return;
        }

        $old = $scope->access_type;
        $scope->update(['access_type' => $newType]);

        activity('external_access_management')
            ->performedOn($scope)
            ->causedBy($actor)
            ->withProperties([
                'old_access_type' => $old->value,
                'new_access_type' => $newType->value,
            ])
            ->log('scope_access_type_changed');
    }

    /**
     * Links an external access to a different CRM contact. Pending submissions reference the
     * old contact and are therefore superseded inside the same transaction.
     */
    public function relink(ExternalAccess $access, CrmContact $newContact, User $actor): void
    {
        if ($access->crm_contact_id === $newContact->id) {
            return; // noop
        }

        $oldContactId = $access->crm_contact_id;

        $this->db->transaction(function () use ($access, $newContact, $actor, $oldContactId): void {
            ExternalPendingSubmission::query()
                ->where('external_access_id', $access->id)
                ->where('status', ExternalSubmissionStatus::PENDING)
                ->update([
                    'status' => ExternalSubmissionStatus::SUPERSEDED,
                    'reviewed_by_user_id' => $actor->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => __('Superseded due to contact re-link'),
                ]);

            $access->update(['crm_contact_id' => $newContact->id]);

            activity('external_access_management')
                ->performedOn($access)
                ->causedBy($actor)
                ->withProperties([
                    'old_crm_contact_id' => $oldContactId,
                    'new_crm_contact_id' => $newContact->id,
                ])
                ->log('access_relinked');
        });
    }
}
