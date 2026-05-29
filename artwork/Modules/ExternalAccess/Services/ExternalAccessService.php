<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Exceptions\ConfidentialMandatoryFieldsMissingException;
use Artwork\Modules\ExternalAccess\Exceptions\InternalUserEmailConflictException;
use Artwork\Modules\ExternalAccess\Exceptions\UnsupportedContactTypeException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessRepository;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessScopeRepository;
use Artwork\Modules\ExternalAccess\Repositories\ExternalInvitationRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class ExternalAccessService
{
    public function __construct(
        private readonly ExternalAccessRepository $externalAccessRepository,
        private readonly ExternalLoginService $externalLoginService,
        private readonly ExternalInvitationRepository $invitationRepository,
        private readonly ExternalAccessScopeRepository $scopeRepository,
        private readonly SourceEntityFactoryRegistry $factoryRegistry,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findByEmail(string $email): ?ExternalAccess
    {
        return $this->externalAccessRepository->findByEmail($email);
    }

    /**
     * @throws InternalUserEmailConflictException
     */
    public function findExistingByEmail(string $email): ?ExternalAccess
    {
        $normalized = mb_strtolower(trim($email));
        $this->guardAgainstInternalUserEmail($normalized);

        return $this->externalAccessRepository->findByEmail($normalized);
    }

    /**
     * Main entry point for inviting an external person.
     *
     * @throws InternalUserEmailConflictException
     * @throws ConfidentialMandatoryFieldsMissingException
     * @throws UnsupportedContactTypeException
     */
    public function invite(InviteExternalCommand $command): ExternalAccess
    {
        return DB::transaction(function () use ($command): ExternalAccess {
            $email = $command->normalizedEmail();

            $this->guardAgainstInternalUserEmail($email);

            $external = $this->findOrCreateExternalAccess($command, $email);

            foreach ($command->tabScopes as $tabScope) {
                $this->scopeRepository->addOrUpdateScope(
                    externalAccess: $external,
                    projectId: (int) $command->sourceReferenceProjectId,
                    projectTabId: $tabScope->projectTabId,
                    accessType: $tabScope->accessType,
                    validFrom: $tabScope->validFrom,
                    validTo: $tabScope->validTo,
                    grantedByUserId: $command->invitedBy->id,
                );
            }

            $invitation = $this->invitationRepository->create([
                'external_access_id' => $external->id,
                'invited_by_user_id' => $command->invitedBy->id,
                'source' => $command->source->value,
                'source_reference_id' => $command->sourceReferenceProjectId,
                'email_sent_at' => now(),
            ]);

            $project = $command->sourceReferenceProjectId !== null
                ? Project::query()->find($command->sourceReferenceProjectId)
                : null;

            $request = request();
            $this->externalLoginService->requestLoginLink(
                email: $external->email,
                ip: $request?->ip(),
                userAgent: $request?->userAgent(),
                isInvitation: true,
                invitedBy: $command->invitedBy,
                invitedFromProject: $project,
            );

            $this->logger->info('External access invited', [
                'external_access_id' => $external->id,
                'invitation_id' => $invitation->id,
                'invited_by' => $command->invitedBy->id,
                'source' => $command->source->value,
            ]);

            return $external;
        });
    }

    private function findOrCreateExternalAccess(InviteExternalCommand $command, string $email): ExternalAccess
    {
        // Path 1: already an external identity for this email -> reuse, extend scope additively.
        $existing = $this->externalAccessRepository->findByEmail($email);
        if ($existing !== null) {
            return $this->reuseExistingExternalAccess($existing, $command);
        }

        // Path 2: email belongs to an existing CRM entity, but no external access yet.
        $crmContact = $this->resolveCrmContactByEmail($email);
        if ($crmContact !== null) {
            return $this->externalAccessRepository->create([
                'email' => $email,
                'crm_contact_id' => $crmContact->id,
                'invited_by_user_id' => $command->invitedBy->id,
                'crm_access_expires_at' => $this->resolveCrmAccessExpiry($command),
            ]);
        }

        // Path 3: unknown email -> create source entity + CRM contact + external access.
        return $this->createFullSet($command, $email);
    }

    private function reuseExistingExternalAccess(
        ExternalAccess $existing,
        InviteExternalCommand $command,
    ): ExternalAccess {
        $newExpiry = $this->resolveCrmAccessExpiry($command);

        $attributes = [];

        if (
            $existing->crm_access_expires_at === null
            || $existing->crm_access_expires_at->lt($newExpiry)
        ) {
            $attributes['crm_access_expires_at'] = $newExpiry;
        }

        if ($existing->revoked_at !== null) {
            $attributes['revoked_at'] = null;
            $this->logger->info('Reactivating previously revoked ExternalAccess', [
                'external_access_id' => $existing->id,
            ]);
        }

        if ($attributes !== []) {
            $existing->forceFill($attributes)->save();
        }

        $this->logger->info('Reusing existing ExternalAccess', [
            'external_access_id' => $existing->id,
            'requested_contact_type_id' => $command->crmContactTypeId,
        ]);

        return $existing;
    }

    private function createFullSet(InviteExternalCommand $command, string $email): ExternalAccess
    {
        $contactType = CrmContactType::query()->findOrFail($command->crmContactTypeId);

        if (!$this->factoryRegistry->isInvitable($contactType->slug)) {
            throw UnsupportedContactTypeException::forSlug($contactType->slug);
        }

        $this->guardConfidentialMandatoryFields($contactType, $command);

        $sourceEntity = $this->factoryRegistry->create($contactType->slug, $command);

        $sourceEntity->createCrmContact();
        $sourceEntity->syncToCrm();

        /** @var CrmContact $crmContact */
        $crmContact = $sourceEntity->crmContact()->firstOrFail();

        $this->writeConfidentialValues($crmContact, $command->confidentialFieldValues);

        return $this->externalAccessRepository->create([
            'email' => $email,
            'crm_contact_id' => $crmContact->id,
            'invited_by_user_id' => $command->invitedBy->id,
            'crm_access_expires_at' => $this->resolveCrmAccessExpiry($command),
        ]);
    }

    private function guardAgainstInternalUserEmail(string $email): void
    {
        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw InternalUserEmailConflictException::forEmail($email);
        }
    }

    private function resolveCrmContactByEmail(string $email): ?CrmContact
    {
        // Only entities that actually have an email column are resolvable by email.
        // Artist has no email column and is therefore intentionally excluded here.
        $entityClasses = [
            Freelancer::class,
            ServiceProvider::class,
            Accommodation::class,
            Manufacturer::class,
        ];

        foreach ($entityClasses as $entityClass) {
            $entity = $entityClass::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($entity?->crmContact) {
                return $entity->crmContact;
            }
        }

        return null;
    }

    private function guardConfidentialMandatoryFields(
        CrmContactType $type,
        InviteExternalCommand $command,
    ): void {
        $missing = CrmProperty::query()
            ->whereHas('group', fn (Builder $g) => $g->where('is_confidential', true))
            ->whereHas(
                'contactTypes',
                fn (Builder $ct) => $ct
                    ->where('crm_contact_types.id', $type->id)
                    ->where('crm_contact_type_property.is_required', true),
            )
            ->get()
            ->reject(fn (CrmProperty $property) => array_key_exists(
                (string) $property->id,
                $command->confidentialFieldValues,
            ) || isset($command->confidentialFieldValues[$property->id]))
            ->pluck('name')
            ->values()
            ->all();

        if ($missing !== []) {
            throw ConfidentialMandatoryFieldsMissingException::forFields($missing);
        }
    }

    /**
     * @param array<string, string> $values propertyId => value
     */
    private function writeConfidentialValues(CrmContact $crmContact, array $values): void
    {
        foreach ($values as $propertyId => $value) {
            CrmPropertyValue::updateOrCreate(
                [
                    'crm_contact_id' => $crmContact->id,
                    'crm_property_id' => (int) $propertyId,
                ],
                ['value' => $value],
            );
        }
    }

    private function resolveCrmAccessExpiry(InviteExternalCommand $command): CarbonImmutable
    {
        if ($command->crmAccessExpiresAt !== null) {
            return $command->crmAccessExpiresAt;
        }

        return CarbonImmutable::instance(
            app(ExternalAccessSettingsResolver::class)->defaultCrmAccessExpiry()->toDateTime()
        );
    }
}
