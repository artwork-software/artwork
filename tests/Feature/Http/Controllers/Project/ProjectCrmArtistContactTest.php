<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectCrmArtistContactTest extends FeatureTestCase
{
    private function artistContactType(): CrmContactType
    {
        return CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'artist'],
            ['name' => 'Künstler*in', 'is_system' => true, 'is_active' => true]
        );
    }

    private function createArtistContact(string $displayName): CrmContact
    {
        return CrmContact::create([
            'crm_contact_type_id' => $this->artistContactType()->id,
            'display_name' => $displayName,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_link_artist_crm_contact_to_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');

        $response = $this->postJson(route('projects.crm-contacts.store', $project), [
            'crm_contact_id' => $contact->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('linked_crm_contacts.0.display_name', 'Anna Beispielkünstlerin');

        $this->assertDatabaseHas('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function contact_of_other_type_cannot_be_linked(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $otherType = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'freelancer'],
            ['name' => 'Freelancer', 'is_system' => true, 'is_active' => true]
        );
        $contact = CrmContact::create([
            'crm_contact_type_id' => $otherType->id,
            'display_name' => 'Kein Künstler',
            'is_active' => true,
        ]);

        $this->postJson(route('projects.crm-contacts.store', $project), [
            'crm_contact_id' => $contact->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function user_without_write_access_cannot_link(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');

        $this->postJson(route('projects.crm-contacts.store', $project), [
            'crm_contact_id' => $contact->id,
        ])->assertForbidden();
    }

    #[Test]
    public function admin_can_unlink_artist_crm_contact(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');
        $project->crmContacts()->attach($contact->id);

        $this->deleteJson(route('projects.crm-contacts.destroy', [$project, $contact]))
            ->assertOk()
            ->assertJsonPath('linked_crm_contacts', []);

        $this->assertDatabaseMissing('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function project_search_finds_project_by_linked_crm_artist_name(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Projekt ohne Namenstreffer']);
        $contact = $this->createArtistContact('Zufallsname Xyzabc');
        $project->crmContacts()->attach($contact->id);

        $response = $this->getJson(route('projects.search', ['query' => 'Xyzabc']));

        $response->assertOk();
        $this->assertContains(
            $project->id,
            collect($response->json())->pluck('id')->all()
        );
        $this->assertStringContainsString(
            'Zufallsname Xyzabc',
            collect($response->json())->firstWhere('id', $project->id)['artists'] ?? ''
        );
    }

    #[Test]
    public function artist_name_tab_payload_contains_linked_crm_contacts(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['artists' => 'Freitext-Künstlerin']);
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');
        $project->crmContacts()->attach($contact->id);

        $this->getJson(route('projects.tabs.artist-name', $project))
            ->assertOk()
            ->assertJsonPath('artist_name', 'Freitext-Künstlerin')
            ->assertJsonPath('linked_crm_contacts.0.id', $contact->id)
            ->assertJsonPath('linked_crm_contacts.0.display_name', 'Anna Beispielkünstlerin');
    }

    #[Test]
    public function project_update_without_crm_field_keeps_existing_links(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');
        $project->crmContacts()->attach($contact->id);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
        ]);

        $this->assertDatabaseHas('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function project_update_with_crm_field_syncs_links(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $oldContact = $this->createArtistContact('Alte Verknüpfung');
        $newContact = $this->createArtistContact('Neue Verknüpfung');
        $project->crmContacts()->attach($oldContact->id);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'crm_artist_contact_ids' => [$newContact->id],
        ]);

        $this->assertDatabaseMissing('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $oldContact->id,
        ]);
        $this->assertDatabaseHas('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $newContact->id,
        ]);
    }

    #[Test]
    public function crm_contact_show_page_lists_linked_projects(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Verknüpftes Projekt']);
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');
        $project->crmContacts()->attach($contact->id);

        $response = $this->get(route('crm.contacts.show', $contact));

        $response->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('CRM/Show')
                    ->where('linkedProjects.0.id', $project->id)
                    ->where('linkedProjects.0.name', 'Verknüpftes Projekt')
            );
    }

    #[Test]
    public function crm_contact_show_page_without_links_has_empty_project_list(): void
    {
        $this->actingAsAdmin();
        $contact = $this->createArtistContact('Anna Beispielkünstlerin');

        $this->get(route('crm.contacts.show', $contact))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('CRM/Show')
                    ->where('linkedProjects', [])
                    ->where('firstProjectTabId', null)
            );
    }

    #[Test]
    public function contact_mask_returns_editable_artist_properties_without_uploads(): void
    {
        $this->actingAsAdmin();
        $artistType = $this->artistContactType();

        $group = \Artwork\Modules\Crm\Models\CrmPropertyGroup::create([
            'name' => 'Maskentest-Gruppe',
            'is_confidential' => false,
        ]);
        $textProperty = \Artwork\Modules\Crm\Models\CrmProperty::create([
            'crm_property_group_id' => $group->id,
            'name' => 'Maskentest-Feld',
            'type' => 'text',
        ]);
        $uploadProperty = \Artwork\Modules\Crm\Models\CrmProperty::create([
            'crm_property_group_id' => $group->id,
            'name' => 'Maskentest-Upload',
            'type' => 'upload',
        ]);
        $artistType->properties()->syncWithoutDetaching([
            $textProperty->id => ['is_required' => true],
            $uploadProperty->id => [],
        ]);

        $response = $this->getJson(route('crm.contacts.mask', ['type_slug' => 'artist']));

        $response->assertOk()
            ->assertJsonPath('contact_type.slug', 'artist');

        $properties = collect($response->json('groups'))
            ->firstWhere('name', 'Maskentest-Gruppe')['properties'] ?? [];
        $names = collect($properties)->pluck('name');

        $this->assertTrue($names->contains('Maskentest-Feld'));
        $this->assertFalse($names->contains('Maskentest-Upload'));
        $this->assertTrue(
            (bool) collect($properties)->firstWhere('name', 'Maskentest-Feld')['is_required']
        );
    }

    #[Test]
    public function contact_store_returns_json_contact_that_can_be_linked(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $artistType = $this->artistContactType();

        $storeResponse = $this->postJson(route('crm.contacts.store'), [
            'crm_contact_type_id' => $artistType->id,
            'display_name' => 'Frisch Angelegt',
        ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('display_name', 'Frisch Angelegt')
            ->assertJsonPath('contact_type.slug', 'artist');

        $contactId = $storeResponse->json('id');

        $this->postJson(route('projects.crm-contacts.store', $project), [
            'crm_contact_id' => $contactId,
        ])->assertOk();

        $this->assertDatabaseHas('crm_contact_project', [
            'project_id' => $project->id,
            'crm_contact_id' => $contactId,
        ]);
    }

    #[Test]
    public function contact_store_still_redirects_for_web_requests(): void
    {
        $this->actingAsAdmin();
        $artistType = $this->artistContactType();

        $response = $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $artistType->id,
            'display_name' => 'Redirect Kontakt',
        ]);

        $contact = CrmContact::query()->where('display_name', 'Redirect Kontakt')->firstOrFail();
        $response->assertRedirect(route('crm.contacts.show', $contact));
    }

    #[Test]
    public function crm_contact_search_can_filter_by_type_slug(): void
    {
        $this->actingAsAdmin();
        $artist = $this->createArtistContact('Slugsuche Künstlerin');

        $otherType = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'freelancer'],
            ['name' => 'Freelancer', 'is_system' => true, 'is_active' => true]
        );
        CrmContact::create([
            'crm_contact_type_id' => $otherType->id,
            'display_name' => 'Slugsuche Freelancer',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('crm.contacts.search', [
            'search' => 'Slugsuche',
            'type_slug' => 'artist',
        ]));

        $response->assertOk();
        $names = collect($response->json())->pluck('display_name');
        $this->assertTrue($names->contains('Slugsuche Künstlerin'));
        $this->assertFalse($names->contains('Slugsuche Freelancer'));
    }
}
