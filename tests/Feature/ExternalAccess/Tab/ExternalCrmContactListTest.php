<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ExternalCrmContactListTest extends TestCase
{
    use CrmContactListFixtures;

    #[Test]
    public function external_sees_pre_linked_contacts_without_confidential_values_and_read_only(): void
    {
        $context = $this->crmContactListContext();
        $contact = $this->preLinkedContact($context);
        $this->actingAs($context['external'], 'external');

        $response = $this->getJson(route('external.project.tab.crm-contacts.index', $this->externalRouteParams($context)))
            ->assertOk()
            ->assertJsonPath('contacts.0.id', $contact->id)
            ->assertJsonPath('contacts.0.can_edit', false)
            ->assertJsonPath('contacts.0.created_by_me', false)
            ->assertJsonPath('contact_types.0.id', $context['type']->id);

        $fieldNames = collect($response->json('contacts.0.fields'))->pluck('name')->all();
        $this->assertSame([$context['phone']->name], $fieldNames);
        $this->assertStringNotContainsString('DE00 SECRET', $response->getContent());
    }

    #[Test]
    public function external_mask_hides_confidential_properties(): void
    {
        $context = $this->crmContactListContext();
        $this->actingAs($context['external'], 'external');

        $response = $this->getJson(route(
            'external.project.tab.crm-contacts.mask',
            $this->externalRouteParams($context),
        ) . '?contact_type_id=' . $context['type']->id)->assertOk();

        $propertyIds = collect($response->json('groups'))->flatMap(fn (array $group) => $group['properties'])->pluck('id')->all();
        $this->assertSame([$context['phone']->id], $propertyIds);
    }

    #[Test]
    public function external_creates_contact_without_confidential_mandatory_fields(): void
    {
        $context = $this->crmContactListContext();
        $this->actingAs($context['external'], 'external');

        $response = $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Mara Tänzerin',
            'property_values' => [
                $context['phone']->id => '0151 999',
                // vertraulich — darf extern nicht geschrieben werden
                $context['iban']->id => 'DE99 HACK',
            ],
        ])->assertCreated()
            ->assertJsonPath('contact.display_name', 'Mara Tänzerin')
            ->assertJsonPath('contact.created_by_me', true)
            ->assertJsonPath('contact.can_edit', true);

        $contact = CrmContact::query()->findOrFail($response->json('contact.id'));
        $this->assertSame($context['external']->id, (int) $contact->created_by_external_access_id);
        $this->assertSame('0151 999', $contact->propertyValues()->where('crm_property_id', $context['phone']->id)->value('value'));
        $this->assertFalse($contact->propertyValues()->where('crm_property_id', $context['iban']->id)->exists());

        $entry = ProjectComponentCrmContact::query()->where('crm_contact_id', $contact->id)->firstOrFail();
        $this->assertSame($context['project']->id, $entry->project_id);
        $this->assertSame($context['external']->id, (int) $entry->created_by_external_access_id);
        $this->assertNull($entry->reviewed_at);
    }

    #[Test]
    public function public_mandatory_fields_are_enforced(): void
    {
        $context = $this->crmContactListContext();
        $this->actingAs($context['external'], 'external');

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Ohne Telefon',
            'property_values' => [],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['property_values.' . $context['phone']->id]);
    }

    #[Test]
    public function contact_type_must_be_allowed_by_the_component(): void
    {
        $context = $this->crmContactListContext();
        $otherType = CrmContactType::create(['name' => 'Andere', 'slug' => 'other-' . uniqid(), 'is_system' => false, 'is_active' => true]);
        $this->actingAs($context['external'], 'external');

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $otherType->id,
            'display_name' => 'Falscher Typ',
        ])->assertStatus(422);

        $this->assertFalse(CrmContact::query()->where('display_name', 'Falscher Typ')->exists());
    }

    #[Test]
    public function external_can_edit_and_remove_own_contacts_only(): void
    {
        $context = $this->crmContactListContext();
        $foreign = $this->preLinkedContact($context);
        $this->actingAs($context['external'], 'external');

        $ownId = $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Eigene Person',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated()->json('contact.id');

        $this->patchJson(route('external.project.tab.crm-contacts.update', $this->externalRouteParams($context, ['crmContact' => $ownId])), [
            'display_name' => 'Eigene Person (neu)',
            'property_values' => [$context['phone']->id => '2'],
        ])->assertOk()->assertJsonPath('contact.display_name', 'Eigene Person (neu)');

        $this->patchJson(route('external.project.tab.crm-contacts.update', $this->externalRouteParams($context, ['crmContact' => $foreign->id])), [
            'display_name' => 'Übernommen',
        ])->assertForbidden();
        $this->deleteJson(route('external.project.tab.crm-contacts.destroy', $this->externalRouteParams($context, ['crmContact' => $foreign->id])))
            ->assertForbidden();

        $this->deleteJson(route('external.project.tab.crm-contacts.destroy', $this->externalRouteParams($context, ['crmContact' => $ownId])))
            ->assertOk();

        // eigener, sonst nirgends genutzter Kontakt landet im CRM-Papierkorb
        $this->assertSoftDeleted('crm_contacts', ['id' => $ownId]);
        $this->assertSame('Bestehende Person', $foreign->fresh()->display_name);
    }

    #[Test]
    public function read_only_scope_cannot_create_contacts(): void
    {
        $context = $this->crmContactListContext(write: false);
        $this->actingAs($context['external'], 'external');

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Nicht erlaubt',
        ])->assertForbidden();
    }

    #[Test]
    public function component_outside_the_shared_tab_is_not_reachable(): void
    {
        $context = $this->crmContactListContext();
        $otherComponent = Component::create([
            'name' => 'Andere Liste',
            'type' => $context['component']->type,
            'data' => $context['component']->data,
        ]);
        $this->actingAs($context['external'], 'external');

        $this->getJson(route('external.project.tab.crm-contacts.index', $this->externalRouteParams($context, [
            'component' => $otherComponent->id,
        ])))->assertNotFound();
    }

    #[Test]
    public function maximum_number_of_contacts_is_enforced(): void
    {
        $context = $this->crmContactListContext();
        $context['component']->update(['data' => array_merge($context['component']->data, ['max_contacts' => 1])]);
        $this->preLinkedContact($context);
        $this->actingAs($context['external'], 'external');

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Zu viel',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertStatus(422);
    }

    #[Test]
    public function artists_are_also_linked_as_project_artists(): void
    {
        $artistType = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'artist'],
            ['name' => 'Künstler*in', 'is_system' => true, 'is_active' => true],
        );
        if ($artistType->trashed() || !$artistType->is_active) {
            $artistType->restore();
            $artistType->update(['is_active' => true]);
        }
        $context = $this->crmContactListContext(type: $artistType);
        $this->actingAs($context['external'], 'external');

        // Der System-Typ kann in der Test-DB weitere Pflichtfelder haben → alle Maskenfelder füllen
        $mask = $this->getJson(route('external.project.tab.crm-contacts.mask', $this->externalRouteParams($context))
            . '?contact_type_id=' . $artistType->id)->assertOk();
        $values = collect($mask->json('groups'))
            ->flatMap(fn (array $group) => $group['properties'])
            ->mapWithKeys(fn (array $property) => [$property['id'] => $property['type'] === 'date' ? '2026-10-01' : '1'])
            ->all();

        $contactId = $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $artistType->id,
            'display_name' => 'Compagnie Luna',
            'property_values' => $values,
        ])->assertCreated()->json('contact.id');

        $this->assertDatabaseHas('crm_contact_project', [
            'project_id' => $context['project']->id,
            'crm_contact_id' => $contactId,
        ]);
    }
}
