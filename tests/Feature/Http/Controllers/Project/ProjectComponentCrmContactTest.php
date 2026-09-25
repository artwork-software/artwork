<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Komponente „CRM-Kontaktliste“ aus interner Sicht.
 */
final class ProjectComponentCrmContactTest extends TestCase
{
    use CrmContactListFixtures;

    /**
     * @return array<string, mixed>
     */
    private function params(array $context, array $extra = []): array
    {
        return array_merge(['project' => $context['project']->id, 'component' => $context['component']->id], $extra);
    }

    private function writer(array $context, array $permissions = []): User
    {
        $user = $this->actingAsUserWith($permissions);
        $context['project']->users()->attach($user->id, ['can_write' => true]);

        return $user;
    }

    #[Test]
    public function project_writer_creates_a_reviewed_contact(): void
    {
        $context = $this->crmContactListContext();
        $writer = $this->writer($context);

        $contactId = $this->postJson(route('projects.components.crm-contacts.store', $this->params($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Intern angelegt',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated()->json('contact.id');

        $entry = ProjectComponentCrmContact::query()->where('crm_contact_id', $contactId)->firstOrFail();
        $this->assertSame($writer->id, (int) $entry->created_by_user_id);
        $this->assertNotNull($entry->reviewed_at);
    }

    #[Test]
    public function read_only_team_member_sees_the_list_but_cannot_change_it(): void
    {
        $context = $this->crmContactListContext();
        $contact = $this->preLinkedContact($context);
        $reader = $this->actingAsUserWith([]);
        $context['project']->users()->attach($reader->id, ['can_write' => false]);

        $this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))
            ->assertOk()
            ->assertJsonPath('can_write', false)
            ->assertJsonPath('contacts.0.id', $contact->id);

        $this->postJson(route('projects.components.crm-contacts.store', $this->params($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Nicht erlaubt',
        ])->assertForbidden();
        $this->deleteJson(route('projects.components.crm-contacts.destroy', $this->params($context, ['crmContact' => $contact->id])))
            ->assertForbidden();
    }

    #[Test]
    public function linking_existing_contacts_requires_crm_access_and_an_allowed_type(): void
    {
        $context = $this->crmContactListContext();
        $existing = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Aus dem CRM', 'is_active' => true]);
        $otherType = CrmContactType::create(['name' => 'Andere', 'slug' => 'other-' . uniqid(), 'is_system' => false, 'is_active' => true]);
        $wrongType = CrmContact::create(['crm_contact_type_id' => $otherType->id, 'display_name' => 'Falscher Typ', 'is_active' => true]);

        $this->writer($context);
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), ['crm_contact_id' => $existing->id])
            ->assertForbidden();

        $this->writer($context, [PermissionEnum::CRM_VIEW]);
        $this->getJson(route('projects.components.crm-contacts.search', $this->params($context)) . '?search=CRM')
            ->assertOk()
            ->assertJsonPath('0.id', $existing->id);
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), ['crm_contact_id' => $existing->id])
            ->assertCreated();
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), ['crm_contact_id' => $wrongType->id])
            ->assertStatus(422);
    }

    #[Test]
    public function internal_removal_only_unlinks_the_contact(): void
    {
        $context = $this->crmContactListContext();
        $contact = $this->preLinkedContact($context);
        $this->writer($context);

        $this->deleteJson(route('projects.components.crm-contacts.destroy', $this->params($context, ['crmContact' => $contact->id])))
            ->assertOk();

        $this->assertFalse(ProjectComponentCrmContact::query()->where('crm_contact_id', $contact->id)->exists());
        $this->assertNotSoftDeleted('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function confidential_values_are_only_shown_to_users_allowed_to_see_them(): void
    {
        $context = $this->crmContactListContext();
        $this->preLinkedContact($context);

        $this->writer($context);
        $plain = $this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))->assertOk();
        $this->assertStringNotContainsString('DE00 SECRET', $plain->getContent());

        $this->writer($context, [PermissionEnum::CRM_MANAGER]);
        $manager = $this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))->assertOk();
        $this->assertStringContainsString('DE00 SECRET', $manager->getContent());
    }

    #[Test]
    public function merging_a_duplicate_moves_it_into_the_existing_contact(): void
    {
        $context = $this->crmContactListContext();
        $existing = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Mara', 'is_active' => true]);

        // extern angelegte Dublette
        $this->actingAs($context['external'], 'external');
        $duplicateId = $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'mara ',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated()->json('contact.id');

        \Illuminate\Support\Facades\Auth::shouldUse('web');
        $this->writer($context, [PermissionEnum::CRM_MANAGER]);
        $this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))
            ->assertOk()
            ->assertJsonPath('contacts.0.possible_duplicates.0.id', $existing->id);

        $this->post(route('crm.duplicates.merge'), ['primary_id' => $existing->id, 'duplicate_ids' => [$duplicateId]])
            ->assertRedirect();

        $entry = ProjectComponentCrmContact::query()
            ->where('project_id', $context['project']->id)
            ->where('component_id', $context['component']->id)
            ->firstOrFail();
        $this->assertSame($existing->id, (int) $entry->crm_contact_id);
        $this->assertNotNull($entry->reviewed_at);
    }
}
