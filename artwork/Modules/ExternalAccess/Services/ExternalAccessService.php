<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Exceptions\AccessNotActiveException;
use Artwork\Modules\ExternalAccess\Exceptions\ConfidentialMandatoryFieldsMissingException;
use Artwork\Modules\ExternalAccess\Exceptions\EmailLinkedToOtherContactException;
use Artwork\Modules\ExternalAccess\Exceptions\InternalUserEmailConflictException;
use Artwork\Modules\ExternalAccess\Exceptions\UnsupportedContactTypeException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
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
        private readonly ExternalContactTypeInvitabilityService $invitabilityService,
        private readonly CrmContactEmailResolver $emailResolver,
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
            $contact = $command->crmContactId !== null
                ? CrmContact::query()->findOrFail($command->crmContactId)
                : null;

            // Bestehender Kontakt ohne angegebene Adresse: die am Kontakt hinterlegte E-Mail nutzen.
            $email = $command->normalizedEmail();
            if ($email === '' && $contact !== null) {
                $email = (string) $this->emailResolver->resolve($contact);
            }
            if ($email === '') {
                throw new \InvalidArgumentException('An email address is required to invite an external person.');
            }

            $this->guardAgainstInternalUserEmail($email);

            $external = match (true) {
                $contact !== null => $this->findOrCreateExternalAccessForContact($contact, $command, $email),
                $command->isTabOnlyInvitation() => $this->findOrCreateTabOnlyAccess($command, $email),
                default => $this->findOrCreateExternalAccess($command, $email),
            };

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

    /**
     * Schickt die Einladungsmail erneut (neuer Magic-Link). Nur möglich, solange der Zugang noch
     * irgendeinen aktiven Bereich hat, sonst würde der Link ins Leere führen.
     *
     * @throws AccessNotActiveException
     */
    public function resendInvitation(ExternalAccess $external, User $actor): ExternalInvitation
    {
        if (!$external->hasAnyActiveAccess()) {
            throw AccessNotActiveException::forAccess($external);
        }

        /** @var ExternalInvitation|null $lastInvitation */
        $lastInvitation = $external->invitations()->latest('id')->first();
        $projectId = $lastInvitation?->source_reference_id;
        $project = $projectId !== null ? Project::query()->find($projectId) : null;

        $invitation = $this->invitationRepository->create([
            'external_access_id' => $external->id,
            'invited_by_user_id' => $actor->id,
            'source' => $lastInvitation?->source ?? InviteSource::CRM_INDEX->value,
            'source_reference_id' => $project?->id,
            'email_sent_at' => now(),
        ]);

        $request = request();
        $this->externalLoginService->requestLoginLink(
            email: $external->email,
            ip: $request?->ip(),
            userAgent: $request?->userAgent(),
            isInvitation: true,
            invitedBy: $actor,
            invitedFromProject: $project,
        );

        activity('external_access_management')
            ->performedOn($external)
            ->causedBy($actor)
            ->withProperties(['invitation_id' => $invitation->id])
            ->log('invitation_resent');

        return $invitation;
    }

    /**
     * Einladung eines bestehenden CRM-Kontakts: Der Zugang hängt an der E-Mail. Gibt es für die
     * Adresse schon einen Zugang an diesem Kontakt, wird er verlängert/reaktiviert; hängt er an
     * einem anderen Kontakt, ist das ein Fehler (bewusst kein stilles Umhängen). Hat der Kontakt
     * noch keine Adresse, wird die eingeladene Adresse am Kontakt nachgetragen.
     */
    private function findOrCreateExternalAccessForContact(
        CrmContact $contact,
        InviteExternalCommand $command,
        string $email,
    ): ExternalAccess {
        $existing = $this->externalAccessRepository->findByEmail($email);
        if ($existing !== null) {
            if ((int) $existing->crm_contact_id !== (int) $contact->id) {
                throw EmailLinkedToOtherContactException::forEmail($email);
            }

            return $this->reuseExistingExternalAccess($existing, $command);
        }

        $this->emailResolver->storeIfMissing($contact, $email);

        return $this->externalAccessRepository->create([
            'email' => $email,
            'crm_contact_id' => $contact->id,
            'invited_by_user_id' => $command->invitedBy->id,
            'crm_access_expires_at' => $this->resolveCrmAccessExpiry($command),
        ]);
    }

    /**
     * Einladung aus dem Projekt-Tab: Der Zugang hängt nur an der E-Mail (plus optionalem Namen). Es wird
     * kein CRM-Kontakt angelegt und kein CRM-Zugang vergeben; ein bestehender Zugang wird wiederverwendet
     * (bei Widerruf reaktiviert), ohne seinen CRM-Zugang zu verlängern.
     */
    private function findOrCreateTabOnlyAccess(InviteExternalCommand $command, string $email): ExternalAccess
    {
        $existing = $this->externalAccessRepository->findByEmail($email);
        if ($existing !== null) {
            $attributes = [];
            if ($existing->revoked_at !== null) {
                $attributes['revoked_at'] = null;
            }
            if ($command->name !== null && trim((string) $existing->name) === '') {
                $attributes['name'] = $command->name;
            }
            if ($attributes !== []) {
                $existing->forceFill($attributes)->save();
            }

            return $existing;
        }

        return $this->externalAccessRepository->create([
            'email' => $email,
            'name' => $command->name,
            'crm_contact_id' => null,
            'invited_by_user_id' => $command->invitedBy->id,
            'crm_access_expires_at' => null,
        ]);
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
            $attributes['crm_expiry_reminder_sent_at'] = null;
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
        if ($command->crmContactTypeId === null) {
            throw new \InvalidArgumentException('A contact type is required to create a new external contact.');
        }

        $contactType = CrmContactType::query()->findOrFail($command->crmContactTypeId);

        if (!$this->invitabilityService->isInvitable($contactType)) {
            throw UnsupportedContactTypeException::forSlug($contactType->slug);
        }

        $this->guardConfidentialMandatoryFields($contactType, $command);

        $crmContact = $this->invitabilityService->usesGenericPath($contactType)
            ? $this->createStandaloneCrmContact($contactType, $command, $email)
            : $this->createCrmContactFromSourceEntity($contactType, $command);

        $this->writeConfidentialValues($crmContact, $command->confidentialFieldValues);

        return $this->externalAccessRepository->create([
            'email' => $email,
            'crm_contact_id' => $crmContact->id,
            'invited_by_user_id' => $command->invitedBy->id,
            'crm_access_expires_at' => $this->resolveCrmAccessExpiry($command),
        ]);
    }

    /**
     * System contact types: build the dedicated source entity (Freelancer, ServiceProvider, …)
     * which in turn creates and syncs its CRM contact.
     */
    private function createCrmContactFromSourceEntity(
        CrmContactType $contactType,
        InviteExternalCommand $command,
    ): CrmContact {
        $sourceEntity = $this->factoryRegistry->create($contactType->slug, $command);

        $sourceEntity->createCrmContact();
        $sourceEntity->syncToCrm();

        return $sourceEntity->crmContact()->firstOrFail();
    }

    /**
     * Freely created contact types have no source entity. The external person is represented by
     * a standalone CRM contact (entity_type/entity_id stay null), named by the inviter-supplied
     * display name. The email is stored on the ExternalAccess record and, if the contact type
     * exposes an "Email" CRM property, mirrored there so it is visible in the CRM.
     */
    private function createStandaloneCrmContact(
        CrmContactType $contactType,
        InviteExternalCommand $command,
        string $email,
    ): CrmContact {
        $displayName = trim((string) (
            $command->publicFieldValues[ExternalContactTypeInvitabilityService::GENERIC_DISPLAY_NAME_FIELD] ?? ''
        ));

        $crmContact = CrmContact::create([
            'crm_contact_type_id' => $contactType->id,
            'display_name' => $displayName !== '' ? $displayName : $email,
            'is_active' => true,
        ]);

        $this->mirrorEmailToCrmProperty($contactType, $crmContact, $email);

        return $crmContact;
    }

    /**
     * Writes the email to the contact type's "Email" CRM property when one is assigned, so a
     * standalone contact is searchable/visible by email in the CRM.
     */
    private function mirrorEmailToCrmProperty(
        CrmContactType $contactType,
        CrmContact $crmContact,
        string $email,
    ): void {
        $emailProperty = CrmProperty::query()
            ->where('name', 'Email')
            ->whereHas(
                'contactTypes',
                fn (Builder $ct) => $ct->where('crm_contact_types.id', $contactType->id),
            )
            ->first();

        if ($emailProperty === null) {
            return;
        }

        CrmPropertyValue::updateOrCreate(
            [
                'crm_contact_id' => $crmContact->id,
                'crm_property_id' => $emailProperty->id,
            ],
            ['value' => $email],
        );
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
        $entityClasses = [
            Freelancer::class,
            ServiceProvider::class,
            Artist::class,
            Accommodation::class,
            Manufacturer::class,
        ];

        foreach ($entityClasses as $entityClass) {
            $entity = $entityClass::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($entity?->crmContact) {
                return $entity->crmContact;
            }
        }

        // Standalone contacts (freely created types) have no source entity; their email lives in
        // the "Email" CRM property value. Match on that so re-inviting them does not create a
        // duplicate contact.
        return $this->resolveStandaloneCrmContactByEmail($email);
    }

    /**
     * Finds a standalone CRM contact (no source entity) whose "Email" CRM property value matches
     * the given email. Only contacts without a polymorphic source entity are considered, since
     * entity-backed contacts are already resolved by their own email column above.
     */
    private function resolveStandaloneCrmContactByEmail(string $email): ?CrmContact
    {
        return CrmPropertyValue::query()
            ->whereHas('property', fn (Builder $p) => $p->where('name', 'Email'))
            ->whereRaw('LOWER(value) = ?', [$email])
            ->whereHas('contact', fn (Builder $c) => $c->whereNull('entity_type'))
            ->with('contact')
            ->first()
            ?->contact;
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
