<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\Project\Services\ProjectArtistService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * CRM-Kontaktliste mit Rollen und Sonderrolle „Künstler*in“ (JTB Punkt 5 + Künstler*in überall).
 */
final class ProjectCrmContactListRolesTest extends TestCase
{
    use CrmContactListFixtures;

    #[Test]
    public function roles_can_be_added_renamed_and_deleted(): void
    {
        $context = $this->rolesContext();
        $this->writer($context);
        $contact = $this->preLinkedContact($context);

        $this->postJson(route('projects.components.crm-contacts.roles.store', $this->params($context)), ['name' => ' Technik  Leitung '])
            ->assertOk()->assertJsonPath('roles', ['Technik Leitung']);
        $this->patchJson(route('projects.components.crm-contacts.entry-roles.update', $this->params($context, ['crmContact' => $contact->id])), [
            'roles' => ['Technik Leitung', 'Presse'],
        ])->assertOk()->assertJsonPath('contact.roles', ['Technik Leitung', 'Presse']);

        $this->patchJson(route('projects.components.crm-contacts.roles.rename', $this->params($context)), [
            'name' => 'Technik Leitung',
            'new_name' => 'Technische Leitung',
        ])->assertOk()->assertJsonPath('roles', ['Technische Leitung', 'Presse']);
        $this->assertSame(['Technische Leitung', 'Presse'], $this->entry($context, $contact)->roles);

        $this->deleteJson(route('projects.components.crm-contacts.roles.destroy', $this->params($context)), ['name' => 'Presse'])
            ->assertOk()->assertJsonPath('roles', ['Technische Leitung']);
        $this->assertSame(['Technische Leitung'], $this->entry($context, $contact)->roles);

        $index = $this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))->assertOk();
        $index->assertJsonPath('roles_enabled', true)
            ->assertJsonPath('roles', ['Technische Leitung'])
            ->assertJsonPath('contacts.0.roles', ['Technische Leitung']);
        $this->assertContains('Technische Leitung', $index->json('role_suggestions'));
    }

    #[Test]
    public function linking_as_role_or_artist_sets_the_role(): void
    {
        $context = $this->rolesContext();
        $this->writer($context, [PermissionEnum::CRM_VIEW->value]);
        $contact = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Mira Tanz', 'is_active' => true]);

        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $contact->id,
            'roles' => ['Choreografie'],
        ])->assertCreated()->assertJsonPath('contact.roles', ['Choreografie'])->assertJsonPath('contact.is_artist', false);

        // erneut verknüpfen „als Künstler*in“ ergänzt nur die Sonderrolle
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $contact->id,
            'is_artist' => true,
        ])->assertCreated();
        $entry = $this->entry($context, $contact);
        $this->assertTrue($entry->is_artist);
        $this->assertSame(['Choreografie'], $entry->roles);
        $this->assertSame(1, ProjectComponentCrmContact::query()->where('crm_contact_id', $contact->id)->count());
    }

    #[Test]
    public function free_text_artist_role_becomes_the_special_role(): void
    {
        $context = $this->rolesContext();
        $this->writer($context);
        $contact = $this->preLinkedContact($context);

        $this->patchJson(route('projects.components.crm-contacts.entry-roles.update', $this->params($context, ['crmContact' => $contact->id])), [
            'roles' => ['Künstlerin', 'Dramaturgie'],
        ])->assertOk()->assertJsonPath('contact.is_artist', true)->assertJsonPath('contact.roles', ['Dramaturgie']);
    }

    #[Test]
    public function artist_role_drives_the_linked_project_artists(): void
    {
        $context = $this->rolesContext();
        $this->writer($context);
        $contact = $this->preLinkedContact($context, 'Compagnie Luna');
        $project = $context['project'];
        $project->update(['artists' => 'Freie Gruppe']);

        $this->patchJson(route('projects.components.crm-contacts.entry-roles.update', $this->params($context, ['crmContact' => $contact->id])), [
            'is_artist' => true,
        ])->assertOk();
        $this->assertSame([['id' => $contact->id, 'name' => 'Compagnie Luna']], $project->fresh()->linked_artists);
        $this->assertSame('Freie Gruppe, Compagnie Luna', $project->fresh()->artistDisplayText());

        // Umbenennen im CRM wirkt überall
        $contact->update(['display_name' => 'Compagnie Sol']);
        $this->assertSame('Compagnie Sol', $project->fresh()->linked_artists[0]['name']);

        // Entfernen aus der Liste nimmt die Künstler*in weg
        $this->deleteJson(route('projects.components.crm-contacts.destroy', $this->params($context, ['crmContact' => $contact->id])))
            ->assertOk();
        $this->assertNull($project->fresh()->linked_artists);
        $this->assertSame('Freie Gruppe', $project->fresh()->artistDisplayText());
    }

    #[Test]
    public function linked_artists_combine_both_sources_without_duplicates(): void
    {
        $context = $this->rolesContext();
        $contact = $this->preLinkedContact($context, 'Mira Tanz');
        $other = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Ole Klang', 'is_active' => true]);
        $project = $context['project'];
        $project->crmContacts()->attach([$other->id, $contact->id]);
        ProjectComponentCrmContact::query()->where('crm_contact_id', $contact->id)->update(['is_artist' => true]);

        app(ProjectArtistService::class)->refresh($project->id);

        $names = array_column($project->fresh()->linked_artists, 'name');
        $this->assertEqualsCanonicalizing(['Ole Klang', 'Mira Tanz'], $names);
        $this->assertCount(2, $names);

        $this->assertSame('Mira Tanz, Ole Klang', ProjectArtistService::displayText('Mira Tanz', [
            ['id' => 1, 'name' => 'Mira Tanz'],
            ['id' => 2, 'name' => 'Ole Klang'],
        ]));
    }

    #[Test]
    public function list_without_contact_types_allows_all_free_types(): void
    {
        $context = $this->rolesContext();
        $context['component']->update(['data' => [...$context['component']->data, 'contact_type_ids' => []]]);
        $this->writer($context);

        $types = collect($this->getJson(route('projects.components.crm-contacts.index', $this->params($context)))
            ->assertOk()->json('contact_types'))->pluck('id');

        $this->assertTrue($types->contains($context['type']->id));
    }

    #[Test]
    public function crm_contact_page_lists_the_list_link_with_roles(): void
    {
        $context = $this->rolesContext();
        $contact = $this->preLinkedContact($context);
        ProjectComponentCrmContact::query()->where('crm_contact_id', $contact->id)
            ->update(['roles' => json_encode(['Presse']), 'is_artist' => true]);
        $this->actingAsAdmin();

        $this->get(route('crm.contacts.show', $contact))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('linkedProjects.0.list_links.0.roles', ['Presse'])
                ->where('linkedProjects.0.list_links.0.is_artist', true));
    }

    #[Test]
    public function deleting_the_contact_for_good_removes_it_from_the_project_artists(): void
    {
        $context = $this->rolesContext();
        $contact = $this->preLinkedContact($context, 'Compagnie Luna');
        $this->entry($context, $contact)->update(['is_artist' => true]);
        $project = $context['project'];
        $this->assertSame('Compagnie Luna', $project->fresh()->linked_artists[0]['name']);

        // z. B. beim Löschen von User/Freelancer/Dienstleister (gespiegelter Kontakt, FK-Cascade)
        $contact->forceDelete();

        $this->assertNull($project->fresh()->linked_artists);
    }

    #[Test]
    public function deleting_the_list_component_removes_its_project_artists(): void
    {
        $context = $this->rolesContext();
        $contact = $this->preLinkedContact($context, 'Compagnie Luna');
        $this->entry($context, $contact)->update(['is_artist' => true]);
        $this->assertNotNull($context['project']->fresh()->linked_artists);

        $this->actingAsAdmin();
        $this->delete(route('component.destroy', $context['component']))->assertSuccessful();

        $this->assertNull($context['project']->fresh()->linked_artists);
    }

    #[Test]
    public function readers_cannot_change_roles_or_the_artist_role(): void
    {
        $context = $this->rolesContext();
        $contact = $this->preLinkedContact($context, 'Compagnie Luna');
        $reader = $this->actingAsUserWith([]);
        $context['project']->users()->attach($reader->id, ['can_write' => false]);

        $this->postJson(route('projects.components.crm-contacts.roles.store', $this->params($context)), ['name' => 'Regie'])
            ->assertForbidden();
        $this->patchJson(route('projects.components.crm-contacts.entry-roles.update', $this->params($context, ['crmContact' => $contact->id])), [
            'is_artist' => true,
        ])->assertForbidden();

        $this->assertFalse($this->entry($context, $contact)->fresh()->is_artist);
        $this->assertNull($context['project']->fresh()->linked_artists);
    }

    #[Test]
    public function linked_contacts_stay_selectable_for_another_role(): void
    {
        $context = $this->rolesContext();
        $this->writer($context, [PermissionEnum::CRM_VIEW->value]);
        $contact = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Lena Licht', 'is_active' => true]);
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $contact->id,
            'roles' => ['Lichttechnik'],
        ])->assertCreated();

        $this->searchLinkable($context, ['role' => 'Tontechnik'])->assertJsonPath('0.id', $contact->id)
            ->assertJsonPath('0.linked.roles', ['Lichttechnik'])
            ->assertJsonPath('0.linked.is_artist', false);
        // gleiche Rolle (Groß-/Kleinschreibung egal) und ohne Zielrolle: nicht mehr auswählbar
        $this->assertSame([], $this->searchLinkable($context, ['role' => 'lichttechnik'])->json());
        $this->assertSame([], $this->searchLinkable($context, [])->json());
        $this->searchLinkable($context, ['artist' => 1])->assertJsonPath('0.id', $contact->id);

        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $contact->id,
            'roles' => ['Tontechnik'],
        ])->assertCreated()->assertJsonPath('contact.roles', ['Lichttechnik', 'Tontechnik']);
        $this->assertSame(1, ProjectComponentCrmContact::query()->where('component_id', $context['component']->id)->count());

        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $contact->id,
            'is_artist' => true,
        ])->assertCreated();
        $this->assertSame([], $this->searchLinkable($context, ['artist' => 1])->json());
    }

    #[Test]
    public function full_list_still_offers_linked_contacts_for_another_role(): void
    {
        $context = $this->rolesContext();
        $context['component']->update(['data' => [...$context['component']->data, 'max_contacts' => 1]]);
        $this->writer($context, [PermissionEnum::CRM_VIEW->value]);
        $linked = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Kim Bühne', 'is_active' => true]);
        $other = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Kai Bühne', 'is_active' => true]);
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), ['crm_contact_id' => $linked->id])
            ->assertCreated();

        $this->searchLinkable($context, ['role' => 'Bühne', 'search' => 'Bühne'])
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $linked->id);
        $this->assertSame([], $this->searchLinkable($context, ['search' => 'Bühne'])->json());

        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $linked->id,
            'roles' => ['Bühne'],
        ])->assertCreated()->assertJsonPath('contact.roles', ['Bühne']);
        $this->postJson(route('projects.components.crm-contacts.link', $this->params($context)), [
            'crm_contact_id' => $other->id,
            'roles' => ['Bühne'],
        ])->assertStatus(422);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function searchLinkable(array $context, array $query): \Illuminate\Testing\TestResponse
    {
        return $this->getJson(route('projects.components.crm-contacts.search', $this->params($context, [
            'search' => $query['search'] ?? 'Licht',
            ...collect($query)->except('search')->all(),
        ])))->assertOk();
    }

    private function rolesContext(): array
    {
        $context = $this->crmContactListContext();
        $context['component']->update(['data' => [...$context['component']->data, 'roles_enabled' => true]]);

        return $context;
    }

    private function writer(array $context, array $permissions = []): User
    {
        $user = $this->actingAsUserWith($permissions);
        $context['project']->users()->attach($user->id, ['can_write' => true]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function params(array $context, array $extra = []): array
    {
        return array_merge(['project' => $context['project']->id, 'component' => $context['component']->id], $extra);
    }

    private function entry(array $context, CrmContact $contact): ProjectComponentCrmContact
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $context['project']->id)
            ->where('component_id', $context['component']->id)
            ->where('crm_contact_id', $contact->id)
            ->firstOrFail();
    }
}
