<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Contacts\Models\Contact;
use Database\Factories\ContactFactory;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Autorisierung in CRM, Aufenthalten, Stammdaten, Inventar sowie Sage-Passwort und TLS-Verifikation.
 */
final class SecurityAuditCrmResidencyRegressionTest extends FeatureTestCase
{
    // ---------------------------------------------------------------- Helpers

    private function projectWriter(Project $project, ?User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        $project->users()->attach($user, ['can_write' => true]);
        $this->actingAs($user);

        return $user;
    }

    private function projectReader(Project $project, ?User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        $project->users()->attach($user, ['can_write' => false]);
        $this->actingAs($user);

        return $user;
    }

    private function outsider(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function residencyPayload(Project $project, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Artist',
            'project_id' => $project->id,
            'cost_per_night' => 80,
            'daily_allowance' => 28,
            'days' => 2,
            'do_not_save_artist' => true,
        ], $overrides);
    }

    private function accommodationPayload(): array
    {
        $roomType = AccommodationRoomType::query()->create(['name' => 'Suite ' . uniqid()]);

        return [
            'name' => 'Hotel Test',
            'room_types' => [$roomType->id],
            'room_type_costs' => [$roomType->id => 120],
        ];
    }

    private function accommodationCrmContact(Accommodation $accommodation): CrmContact
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'accommodation'],
            ['name' => 'Unterkunft', 'is_system' => true, 'is_active' => true]
        );

        return CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => $accommodation->name,
            'is_active' => true,
            'entity_type' => Accommodation::class,
            'entity_id' => $accommodation->id,
        ]);
    }

    private function plainCrmContact(): CrmContact
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'test-plain'],
            ['name' => 'Test', 'is_system' => false, 'is_active' => true]
        );

        return CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Plain Contact',
            'is_active' => true,
        ]);
    }

    // ------------------------------------------- Künstler*innenaufenthalte

    #[Test]
    public function residency_store_requires_write_access_to_the_route_project(): void
    {
        $project = Project::factory()->create();

        $this->outsider();
        $this->postJson(route('artist-residencies.store', $project), $this->residencyPayload($project))
            ->assertForbidden();

        $this->projectReader($project);
        $this->postJson(route('artist-residencies.store', $project), $this->residencyPayload($project))
            ->assertForbidden();

        $this->assertDatabaseCount('artist_residencies', 0);

        $this->projectWriter($project);
        $this->postJson(route('artist-residencies.store', $project), $this->residencyPayload($project))
            ->assertOk();

        $this->assertDatabaseHas('artist_residencies', ['project_id' => $project->id, 'name' => 'Test Artist']);
    }

    #[Test]
    public function residency_store_ignores_a_foreign_project_id_in_the_body(): void
    {
        $own = Project::factory()->create();
        $foreign = Project::factory()->create();
        $this->projectWriter($own);

        $this->postJson(
            route('artist-residencies.store', $own),
            $this->residencyPayload($own, ['project_id' => $foreign->id])
        )->assertOk();

        $this->assertDatabaseHas('artist_residencies', ['project_id' => $own->id]);
        $this->assertDatabaseMissing('artist_residencies', ['project_id' => $foreign->id]);
    }

    #[Test]
    public function residency_mutations_require_write_access_to_the_residency_project(): void
    {
        $project = Project::factory()->create();
        $residency = ArtistResidency::factory()->create(['project_id' => $project->id, 'name' => 'Original']);

        foreach (['outsider', 'reader'] as $role) {
            $role === 'outsider' ? $this->outsider() : $this->projectReader($project);

            $this->patchJson(
                route('artist-residencies.update', $residency),
                $this->residencyPayload($project, ['id' => $residency->id, 'name' => 'Changed'])
            )->assertForbidden();
            $this->patchJson(route('artist-residencies.update-name', $residency), [
                'field' => 'name', 'value' => 'Changed',
            ])->assertForbidden();
            $this->postJson(route('artist_residencies.duplicate', $residency))->assertForbidden();
            $this->deleteJson(route('artist-residency.destroy', $residency))->assertForbidden();
        }

        $this->assertDatabaseCount('artist_residencies', 1);
        $this->assertDatabaseHas('artist_residencies', ['id' => $residency->id, 'name' => 'Original']);

        $this->projectWriter($project);
        $this->patchJson(route('artist-residencies.update-name', $residency), [
            'field' => 'name', 'value' => 'Changed',
        ])->assertOk();
        $this->postJson(route('artist_residencies.duplicate', $residency))->assertOk();
        $this->assertDatabaseCount('artist_residencies', 2);
        $this->deleteJson(route('artist-residency.destroy', $residency))->assertOk();
        $this->assertDatabaseMissing('artist_residencies', ['id' => $residency->id]);
    }

    #[Test]
    public function residency_cannot_be_moved_into_a_project_without_write_access(): void
    {
        $own = Project::factory()->create();
        $foreign = Project::factory()->create();
        $residency = ArtistResidency::factory()->create(['project_id' => $own->id]);
        $this->projectWriter($own);

        $this->patchJson(
            route('artist-residencies.update', $residency),
            $this->residencyPayload($foreign, ['id' => $residency->id])
        )->assertForbidden();

        $this->assertDatabaseHas('artist_residencies', ['id' => $residency->id, 'project_id' => $own->id]);
    }

    #[Test]
    public function residency_exports_require_view_access_to_the_project(): void
    {
        Excel::fake();
        $project = Project::factory()->create();
        ArtistResidency::factory()->create(['project_id' => $project->id]);

        $this->outsider();
        $this->getJson(route('artist-residencies.export-excel', [$project, 'de']))->assertForbidden();
        $this->postJson(route('artist-residencies.export-pdf', [$project, 'de']))->assertForbidden();
        $this->postJson(route('artist-residencies.export-per-diem-pdf', [$project, 'de']))->assertForbidden();

        $this->projectReader($project);
        $this->get(route('artist-residencies.export-excel', [$project, 'de']))->assertOk();
    }

    // -------------------------------------------------- Künstler*innen

    #[Test]
    public function artist_master_data_follows_project_access(): void
    {
        Excel::fake();
        $project = Project::factory()->create();
        $artist = Artist::query()->create(['name' => 'Original']);

        $this->outsider();
        $this->get(route('artist.index'))->assertForbidden();
        $this->get(route('artist.export'))->assertForbidden();
        $this->postJson(route('artist.store'), ['name' => 'New'])->assertForbidden();
        $this->patchJson(route('artist.update', $artist), ['id' => $artist->id, 'name' => 'Changed'])
            ->assertForbidden();
        $this->deleteJson(route('artist.destroy', $artist))->assertForbidden();

        $this->projectReader($project);
        $this->get(route('artist.index'))->assertOk();
        $this->get(route('artist.export'))->assertOk();
        $this->postJson(route('artist.store'), ['name' => 'New'])->assertForbidden();
        $this->deleteJson(route('artist.destroy', $artist))->assertForbidden();

        $this->assertDatabaseHas('artists', ['id' => $artist->id, 'name' => 'Original']);
        $this->assertDatabaseMissing('artists', ['name' => 'New']);

        $this->projectWriter($project);
        $this->postJson(route('artist.store'), ['name' => 'New'])->assertOk();
        $this->patchJson(route('artist.update', $artist), ['id' => $artist->id, 'name' => 'Changed'])->assertOk();
        $this->assertDatabaseHas('artists', ['name' => 'New']);
        $this->assertDatabaseHas('artists', ['id' => $artist->id, 'name' => 'Changed']);
    }

    // ------------------------------------------------------ Unterkünfte

    #[Test]
    public function accommodation_management_requires_project_write_or_crm_manager(): void
    {
        $project = Project::factory()->create();
        $accommodation = Accommodation::factory()->create(['name' => 'Original']);

        $this->outsider();
        $this->get(route('accommodation.index'))->assertForbidden();
        $this->get(route('accommodation.show', $accommodation))->assertForbidden();
        $this->postJson(route('accommodation.store'), $this->accommodationPayload())->assertForbidden();
        $this->postJson(route('accommodation-room-types.store'), ['name' => 'Loft'])->assertForbidden();
        $this->deleteJson(route('accommodation.destroy', $accommodation))->assertForbidden();

        $this->projectReader($project);
        $this->get(route('accommodation.index'))->assertOk();
        $this->postJson(route('accommodation.store'), $this->accommodationPayload())->assertForbidden();
        $this->patchJson(
            route('accommodation.update', $accommodation),
            array_merge($this->accommodationPayload(), ['name' => 'Changed'])
        )->assertForbidden();

        $this->assertDatabaseHas('accommodations', ['id' => $accommodation->id, 'name' => 'Original']);
        $this->assertDatabaseMissing('accommodations', ['name' => 'Hotel Test']);

        $this->projectWriter($project);
        $this->post(route('accommodation.store'), $this->accommodationPayload())->assertRedirect();
        $this->assertDatabaseHas('accommodations', ['name' => 'Hotel Test']);

        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->delete(route('accommodation.destroy', $accommodation))->assertRedirect();
        $this->assertDatabaseMissing('accommodations', ['id' => $accommodation->id]);
    }

    // --------------------------------------------------- Kontakte-Modul

    #[Test]
    public function contacts_can_only_be_managed_by_whoever_may_edit_the_parent(): void
    {
        $victim = User::factory()->create();
        $provider = ServiceProvider::factory()->create();
        $accommodation = Accommodation::factory()->create();
        $payload = ['name' => 'Injected', 'email' => 'injected@example.test'];

        $attacker = $this->outsider();
        $this->postJson(route('contact.store', ['model' => 'user', 'modelId' => $victim->id]), $payload)
            ->assertForbidden();
        $this->postJson(route('contact.store', ['model' => 'provider', 'modelId' => $provider->id]), $payload)
            ->assertForbidden();
        $this->postJson(
            route('contact.store', ['model' => 'accommodation', 'modelId' => $accommodation->id]),
            $payload
        )->assertForbidden();
        $this->assertDatabaseCount('contacts', 0);

        $foreignContact = (new ContactFactory())->create(['contactable_id' => $victim->id, 'name' => 'Original']);
        $this->patchJson(route('contact.update', $foreignContact), ['name' => 'Changed'])->assertForbidden();
        $this->deleteJson(route('contact.destroy', $foreignContact))->assertForbidden();
        $this->assertDatabaseHas('contacts', ['id' => $foreignContact->id, 'name' => 'Original']);

        $this->projectWriter(Project::factory()->create());
        $this->postJson(
            route('contact.store', ['model' => 'accommodation', 'modelId' => $accommodation->id]),
            $payload
        )->assertOk();
        $this->assertDatabaseHas('contacts', [
            'contactable_type' => Accommodation::class, 'contactable_id' => $accommodation->id, 'name' => 'Injected',
        ]);

        $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);
        $this->postJson(route('contact.store', ['model' => 'provider', 'modelId' => $provider->id]), $payload)
            ->assertOk();
    }

    // ----------------------------------------------------------- CRM

    #[Test]
    public function crm_contact_deletion_requires_crm_manager(): void
    {
        $contact = $this->plainCrmContact();
        $other = $this->plainCrmContact();

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->delete(route('crm.contacts.destroy', $contact))->assertForbidden();
        $this->post(route('crm.contacts.bulk-destroy'), ['ids' => [$contact->id, $other->id]])->assertForbidden();
        $this->assertDatabaseHas('crm_contacts', ['id' => $contact->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('crm_contacts', ['id' => $other->id, 'deleted_at' => null]);

        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->delete(route('crm.contacts.destroy', $contact))->assertRedirect();
        $this->assertSoftDeleted('crm_contacts', ['id' => $contact->id]);
        $this->post(route('crm.contacts.bulk-destroy'), ['ids' => [$other->id]])->assertRedirect();
        $this->assertSoftDeleted('crm_contacts', ['id' => $other->id]);
    }

    #[Test]
    public function crm_profile_image_requires_crm_access(): void
    {
        $contact = $this->plainCrmContact();

        $this->outsider();
        $this->post(route('crm.contacts.profile-image', $contact), [
            'profile_image' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertForbidden();

        $this->assertDatabaseHas('crm_contacts', ['id' => $contact->id, 'profile_image' => null]);
    }

    #[Test]
    public function crm_room_types_require_crm_manager_and_contact_membership(): void
    {
        $accommodation = Accommodation::factory()->create();
        $contact = $this->accommodationCrmContact($accommodation);
        $ownType = AccommodationRoomType::query()->create(['name' => 'Own']);
        $foreignType = AccommodationRoomType::query()->create(['name' => 'Foreign']);
        $contact->roomTypes()->attach($ownType->id, ['accommodation_id' => $accommodation->id, 'cost_per_night' => 1]);

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->patch(route('crm.contacts.room-types.update', $contact), ['room_types' => []])->assertForbidden();
        $this->post(route('crm.contacts.room-types.store', $contact), ['name' => 'X'])->assertForbidden();
        $this->patch(route('crm.contacts.room-types.update-name', [$contact, $ownType]), ['name' => 'Hacked'])
            ->assertForbidden();
        $this->delete(route('crm.contacts.room-types.destroy', [$contact, $ownType]))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $this->patch(route('crm.contacts.room-types.update-name', [$contact, $foreignType]), ['name' => 'Hacked'])
            ->assertNotFound();
        $this->assertDatabaseHas('accommodation_room_types', ['id' => $foreignType->id, 'name' => 'Foreign']);

        $this->patch(route('crm.contacts.room-types.update-name', [$contact, $ownType]), ['name' => 'Renamed'])
            ->assertRedirect();
        $this->assertDatabaseHas('accommodation_room_types', ['id' => $ownType->id, 'name' => 'Renamed']);
    }

    // ------------------------------------------------ Inventar / Materialausgabe

    #[Test]
    public function detailed_article_autosave_respects_restricted_tags(): void
    {
        $article = InventoryArticle::factory()->create(['is_detailed_quantity' => true]);
        $tag = InventoryTag::query()->create(['name' => 'Restricted', 'has_restricted_permissions' => true]);
        $article->tags()->attach($tag->id);
        $detailed = InventoryDetailedQuantityArticle::query()->create([
            'inventory_article_id' => $article->id,
            'name' => 'Original',
            'quantity' => 1,
            'external_id' => 'ext-' . uniqid(),
            'inventory_number' => 'INV-' . uniqid(),
        ]);

        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $this->patchJson(route('inventory-management.articles.detailed.update-field', $detailed), [
            'field' => 'name', 'value' => 'Changed',
        ])->assertForbidden();
        $this->assertDatabaseHas('inventory_detailed_quantity_articles', ['id' => $detailed->id, 'name' => 'Original']);

        $tag->allowedUsers()->attach(auth()->id());
        $this->patchJson(route('inventory-management.articles.detailed.update-field', $detailed), [
            'field' => 'name', 'value' => 'Changed',
        ])->assertOk();
        $this->assertDatabaseHas('inventory_detailed_quantity_articles', ['id' => $detailed->id, 'name' => 'Changed']);
    }

    #[Test]
    public function internal_issue_cannot_be_moved_into_a_project_without_write_access(): void
    {
        $own = Project::factory()->create();
        $foreign = Project::factory()->create();
        $issue = InternalIssue::factory()->create(['project_id' => $own->id]);
        $this->projectWriter($own);

        $this->patchJson(route('issue-of-material.update', $issue), [
            'id' => $issue->id,
            'project_id' => $foreign->id,
        ])->assertForbidden();
        $this->assertDatabaseHas('internal_issues', ['id' => $issue->id, 'project_id' => $own->id]);

        $this->patchJson(route('issue-of-material.update', $issue), [
            'id' => $issue->id,
            'project_id' => $own->id,
        ])->assertUnprocessable();
    }

    // ----------------------------------------------------------------- Sage

    #[Test]
    public function sage_password_never_reaches_the_browser_and_is_kept_when_left_blank(): void
    {
        SageApiSettings::query()->create([
            'host' => 'https://sage.example.test',
            'endpoint' => '/api/v1/bookings',
            'user' => 'artwork',
            'password' => 'top-secret',
            'enabled' => true,
        ]);
        $this->actingAsAdmin();

        $response = $this->get(route('tool.interfaces'))->assertOk();
        $this->assertInertiaWhere($response, 'sageSettings.has_password', true);
        $this->assertInertiaWhere($response, 'sageSettings.verify_ssl', true);
        $props = $response->viewData('page')['props'];
        $this->assertArrayNotHasKey('password', $props['sageSettings']);
        $this->assertStringNotContainsString('top-secret', $response->getContent());

        $this->post(route('tool.interfaces.sage.update'), [
            'host' => 'https://sage.example.test',
            'endpoint' => '/api/v1/bookings',
            'user' => 'artwork',
            'password' => '',
            'enabled' => true,
            'verify_ssl' => false,
        ])->assertRedirect();

        $settings = SageApiSettings::query()->first();
        $this->assertSame('top-secret', $settings->password);
        $this->assertFalse($settings->shouldVerifySsl());

        $this->post(route('tool.interfaces.sage.update'), [
            'host' => 'https://sage.example.test',
            'endpoint' => '/api/v1/bookings',
            'user' => 'artwork',
            'password' => 'new-secret',
            'enabled' => true,
            'verify_ssl' => true,
        ])->assertRedirect();

        $settings = SageApiSettings::query()->first();
        $this->assertSame('new-secret', $settings->password);
        $this->assertTrue($settings->shouldVerifySsl());
        $this->assertArrayNotHasKey('password', $settings->toArray());
    }

    #[Test]
    public function sage_host_must_be_an_http_url(): void
    {
        $this->actingAsAdmin();

        $this->from(route('tool.interfaces'))
            ->post(route('tool.interfaces.sage.update'), [
                'host' => 'sage.example.test',
                'endpoint' => '/api',
                'user' => 'artwork',
                'password' => 'secret',
            ])
            ->assertRedirect(route('tool.interfaces'))
            ->assertSessionHasErrors('host');

        $this->from(route('tool.interfaces'))
            ->post(route('tool.interfaces.sage.update'), [
                'host' => 'ftp://sage.example.test',
                'endpoint' => '/api',
                'user' => 'artwork',
                'password' => 'secret',
            ])
            ->assertSessionHasErrors('host');

        $this->assertDatabaseCount('sage_api_settings', 0);
    }
}
