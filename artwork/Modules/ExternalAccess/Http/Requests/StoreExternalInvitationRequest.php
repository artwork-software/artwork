<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\DTOs\TabScopeInput;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Services\CrmContactEmailResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreExternalInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(ExternalAccessSettingsResolver::class)->isEnabled()
            && $this->user()?->can(PermissionEnum::INVITE_EXTERNAL->value) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Bestehender Kontakt: E-Mail optional (fällt auf die hinterlegte Adresse zurück),
            // Kontaktart ergibt sich aus dem Kontakt.
            'crm_contact_id' => ['nullable', 'integer', 'exists:crm_contacts,id'],
            'email' => [Rule::requiredIf(!$this->filled('crm_contact_id')), 'nullable', 'email:rfc'],
            'crm_contact_type_id' => [
                Rule::requiredIf(!$this->filled('crm_contact_id')),
                'nullable',
                'integer',
                'exists:crm_contact_types,id',
            ],
            'source' => ['required', Rule::enum(InviteSource::class)],
            'source_reference_project_id' => [
                Rule::requiredIf($this->input('source') === InviteSource::PROJECT_TAB->value),
                'nullable',
                'integer',
                'exists:projects,id',
            ],
            'crm_access_expires_at' => ['nullable', 'date', 'after:now'],

            'tab_scopes' => ['array'],
            'tab_scopes.*.project_tab_id' => ['required', 'integer', 'exists:project_tabs,id'],
            'tab_scopes.*.access_type' => ['required', Rule::enum(ExternalAccessType::class)],
            'tab_scopes.*.valid_from' => ['required', 'date'],
            'tab_scopes.*.valid_to' => ['required', 'date', 'after:tab_scopes.*.valid_from'],

            'confidential_field_values' => ['array'],
            'confidential_field_values.*' => ['nullable', 'string'],
            'public_field_values' => ['array'],
            'public_field_values.*' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('crm_contact_id') && !$this->filled('email')) {
                $contact = CrmContact::query()->find($this->input('crm_contact_id'));
                if ($contact === null || app(CrmContactEmailResolver::class)->resolve($contact) === null) {
                    $validator->errors()->add(
                        'email',
                        __('This contact has no email address yet. Please enter one.'),
                    );
                }
            }

            if ($this->input('source') !== InviteSource::PROJECT_TAB->value) {
                return;
            }

            $projectId = $this->input('source_reference_project_id');
            if ($projectId === null) {
                return;
            }

            $project = Project::query()->find($projectId);

            // ProjectTab is a GLOBAL template (no project_id) — there is no "tab belongs to
            // project" relationship to check. The real guard against cross-project scope
            // injection is that the inviter must be allowed to view the source project.
            if ($project === null || $this->user()?->cannot('view', $project)) {
                $validator->errors()->add(
                    'source_reference_project_id',
                    __('You do not have access to the selected project.'),
                );
            }
        });
    }

    public function toCommand(): InviteExternalCommand
    {
        /** @var User $inviter */
        $inviter = $this->user();

        $expiry = $this->input('crm_access_expires_at');

        $tabScopes = array_map(
            static fn (array $scope): TabScopeInput => new TabScopeInput(
                projectTabId: (int) $scope['project_tab_id'],
                accessType: ExternalAccessType::from($scope['access_type']),
                validFrom: CarbonImmutable::parse($scope['valid_from']),
                validTo: CarbonImmutable::parse($scope['valid_to']),
            ),
            $this->input('tab_scopes', []),
        );

        return new InviteExternalCommand(
            email: (string) $this->input('email', ''),
            crmContactTypeId: $this->filled('crm_contact_type_id') ? (int) $this->input('crm_contact_type_id') : null,
            source: InviteSource::from($this->input('source')),
            sourceReferenceProjectId: $this->input('source_reference_project_id') !== null
                ? (int) $this->input('source_reference_project_id')
                : null,
            invitedBy: $inviter,
            crmAccessExpiresAt: $expiry !== null ? CarbonImmutable::parse($expiry) : null,
            tabScopes: $tabScopes,
            confidentialFieldValues: $this->input('confidential_field_values', []),
            publicFieldValues: $this->input('public_field_values', []),
            crmContactId: $this->filled('crm_contact_id') ? (int) $this->input('crm_contact_id') : null,
        );
    }
}
