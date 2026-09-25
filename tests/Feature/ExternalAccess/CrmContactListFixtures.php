<?php

namespace Tests\Feature\ExternalAccess;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;

/**
 * Aufbau für Tests der Komponente „CRM-Kontaktliste“: Kontakttyp mit öffentlicher Pflicht-Eigenschaft
 * („Telefon“) und vertraulicher Pflicht-Eigenschaft („IBAN“), Tab mit der Liste, externer Schreibzugang.
 */
trait CrmContactListFixtures
{
    /**
     * @return array{
     *     type: CrmContactType,
     *     phone: CrmProperty,
     *     iban: CrmProperty,
     *     component: Component,
     *     tab: ProjectTab,
     *     project: Project,
     *     inviter: User,
     *     external: ExternalAccess,
     *     scope: ExternalAccessScope,
     * }
     */
    protected function crmContactListContext(bool $write = true, ?CrmContactType $type = null): array
    {
        $type ??= CrmContactType::create([
            'name' => 'Gast',
            'slug' => 'guest-' . uniqid(),
            'is_system' => false,
            'is_active' => true,
        ]);

        $publicGroup = CrmPropertyGroup::create(['name' => 'Kontakt', 'is_confidential' => false, 'sort_order' => 1]);
        $confidentialGroup = CrmPropertyGroup::create(['name' => 'Vertrag', 'is_confidential' => true, 'sort_order' => 2]);

        $phone = CrmProperty::create([
            'crm_property_group_id' => $publicGroup->id,
            'name' => 'Telefon ' . uniqid(),
            'type' => 'text',
        ]);
        $iban = CrmProperty::create([
            'crm_property_group_id' => $confidentialGroup->id,
            'name' => 'IBAN ' . uniqid(),
            'type' => 'text',
        ]);
        foreach ([$phone, $iban] as $index => $property) {
            $property->contactTypes()->attach($type->id, [
                'sort_order' => $index,
                'is_required' => true,
                'show_in_list' => true,
                'is_filterable' => false,
            ]);
        }

        $component = Component::create([
            'name' => 'Anreisende Personen',
            'type' => ProjectTabComponentEnum::CRM_CONTACT_LIST->value,
            'data' => ['title' => 'Anreisende Personen', 'description' => '', 'contact_type_ids' => [$type->id], 'max_contacts' => null],
            'permission_type' => 'allSeeAndEdit',
        ]);
        $tab = ProjectTab::factory()->create();
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 0]);

        $project = Project::factory()->create();
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->active()->create([
            'invited_by_user_id' => $inviter->id,
            'crm_contact_id' => null,
            'crm_access_expires_at' => null,
            'name' => 'Luna Gastspiel',
        ]);

        $factory = ExternalAccessScope::factory();
        if ($write) {
            $factory = $factory->write();
        }
        $scope = $factory->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
            'granted_by_user_id' => $inviter->id,
        ]);

        return compact('type', 'phone', 'iban', 'component', 'tab', 'project', 'inviter', 'external', 'scope');
    }

    /**
     * Intern (vor der Einladung) verknüpfter Kontakt mit öffentlichem und vertraulichem Wert.
     */
    protected function preLinkedContact(array $context, string $name = 'Bestehende Person'): CrmContact
    {
        $contact = CrmContact::create([
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => $name,
            'is_active' => true,
        ]);
        $contact->propertyValues()->create(['crm_property_id' => $context['phone']->id, 'value' => '0170 123']);
        $contact->propertyValues()->create(['crm_property_id' => $context['iban']->id, 'value' => 'DE00 SECRET']);

        ProjectComponentCrmContact::query()->create([
            'project_id' => $context['project']->id,
            'component_id' => $context['component']->id,
            'crm_contact_id' => $contact->id,
            'reviewed_at' => now(),
        ]);

        return $contact;
    }

    /**
     * @return array<string, mixed>
     */
    protected function externalRouteParams(array $context, array $extra = []): array
    {
        return array_merge([
            'project' => $context['project']->id,
            'tab' => $context['tab']->id,
            'component' => $context['component']->id,
        ], $extra);
    }
}
