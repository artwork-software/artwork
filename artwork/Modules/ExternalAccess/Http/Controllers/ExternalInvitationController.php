<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Artwork\Modules\ExternalAccess\Exceptions\ExternalAccessException;
use Artwork\Modules\ExternalAccess\Http\Requests\StoreExternalInvitationRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\CrmContactEmailResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\ExternalAccess\Services\ExternalContactTypeInvitabilityService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ExternalInvitationController extends Controller
{
    public function __construct(
        private readonly ExternalAccessService $externalAccessService,
        private readonly ExternalContactTypeInvitabilityService $invitabilityService,
        private readonly CrmContactTypeService $contactTypeService,
        private readonly ExternalAccessSettingsResolver $settingsResolver,
        private readonly CrmContactEmailResolver $emailResolver,
    ) {
    }

    /**
     * Active contact types for the invite modal dropdown (used where the page does not already
     * provide them, e.g. the project-tab entry point).
     */
    public function contactTypes(): JsonResponse
    {
        abort_unless(
            $this->settingsResolver->isEnabled()
            && request()->user()?->can(PermissionEnum::INVITE_EXTERNAL->value),
            403,
        );

        $types = $this->contactTypeService->getActive()
            ->map(fn (CrmContactType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
            ])
            ->values();

        return response()->json([
            'contact_types' => $types,
            'defaults' => $this->inviteDefaults(),
        ]);
    }

    /**
     * Vorbelegung des Einladungsdialogs für einen bestehenden CRM-Kontakt: hinterlegte E-Mail,
     * Kontaktart und bereits vorhandene Zugänge (mit freigegebenen Tabs), damit die einladende
     * Person sieht, wohin die Mail geht und was schon freigegeben ist.
     */
    public function inviteInfo(CrmContact $crmContact): JsonResponse
    {
        abort_unless(
            $this->settingsResolver->isEnabled()
            && request()->user()?->can(PermissionEnum::INVITE_EXTERNAL->value),
            403,
        );

        $accesses = $crmContact->externalAccesses()
            ->with(['scopes.project:id,name', 'scopes.projectTab:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ExternalAccess $access) => [
                'id' => $access->id,
                'email' => $access->email,
                'is_active' => $access->hasAnyActiveAccess(),
                'revoked_at' => $access->revoked_at?->toIso8601String(),
                'crm_access_expires_at' => $access->crm_access_expires_at?->toIso8601String(),
                'scopes' => $access->scopes
                    ->filter(fn ($scope) => $scope->valid_to->isFuture())
                    ->map(fn ($scope) => [
                        'project' => $scope->project?->name,
                        'tab' => $scope->projectTab?->name,
                        'valid_to' => $scope->valid_to->toIso8601String(),
                    ])->values()->all(),
            ])
            ->values();

        return response()->json([
            'contact' => [
                'id' => $crmContact->id,
                'display_name' => $crmContact->display_name,
                'email' => $this->emailResolver->resolve($crmContact),
                'contact_type' => $crmContact->contactType ? [
                    'id' => $crmContact->contactType->id,
                    'name' => $crmContact->contactType->name,
                ] : null,
            ],
            'accesses' => $accesses,
            'defaults' => $this->inviteDefaults(),
        ]);
    }

    /**
     * @return array{crm_access_expires_at: string, tab_valid_from: string, tab_valid_to: string}
     */
    private function inviteDefaults(): array
    {
        return [
            'crm_access_expires_at' => $this->settingsResolver->defaultCrmAccessExpiry()->toDateString(),
            'tab_valid_from' => now()->toDateString(),
            'tab_valid_to' => $this->settingsResolver->defaultTabAccessExpiry()->toDateString(),
        ];
    }

    public function store(StoreExternalInvitationRequest $request): JsonResponse
    {
        try {
            $external = $this->externalAccessService->invite($request->toCommand());
        } catch (ExternalAccessException $e) {
            return response()->json(['message' => __($e->getMessage())], 422);
        }

        return response()->json([
            'message' => __('Invitation sent.'),
            'external_access_id' => $external->id,
        ], 201);
    }

    /**
     * Tells the invite modal which fields the inviter must supply for a contact type:
     * the source-entity columns required to create the entity, and the confidential
     * mandatory CRM properties (which the external person will never see).
     */
    public function showContactTypeRequirements(CrmContactType $crmContactType): JsonResponse
    {
        abort_unless(
            $this->settingsResolver->isEnabled()
            && request()->user()?->can(PermissionEnum::INVITE_EXTERNAL->value),
            403,
        );

        $confidentialRequired = CrmProperty::query()
            ->whereHas('group', fn (Builder $g) => $g->where('is_confidential', true))
            ->whereHas(
                'contactTypes',
                fn (Builder $ct) => $ct
                    ->where('crm_contact_types.id', $crmContactType->id)
                    ->where('crm_contact_type_property.is_required', true),
            )
            ->get(['id', 'name'])
            ->map(fn (CrmProperty $p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();

        return response()->json([
            'invitable' => $this->invitabilityService->isInvitable($crmContactType),
            'slug' => $crmContactType->slug,
            'public_required_fields' => $this->invitabilityService->requiredPublicFields($crmContactType),
            'confidential_required_properties' => $confidentialRequired,
        ]);
    }
}
