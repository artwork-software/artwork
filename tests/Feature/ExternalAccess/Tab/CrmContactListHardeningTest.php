<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessScopeRepository;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Absicherungen aus dem Release-Review: Zusammenführen, Löschschutz, Link-Werte, Einladungswege für
 * reine Tab-Zugänge, erneute Einladung.
 */
final class CrmContactListHardeningTest extends TestCase
{
    use CrmContactListFixtures;

    protected function tearDown(): void
    {
        Auth::shouldUse('web');
        parent::tearDown();
    }

    /**
     * @return int ID des extern angelegten Kontakts
     */
    private function createAsExternal(array $context, string $name): int
    {
        $this->actingAs($context['external'], 'external');

        return $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => $name,
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated()->json('contact.id');
    }

    #[Test]
    public function after_merging_into_an_existing_contact_the_external_person_can_no_longer_change_it(): void
    {
        $context = $this->crmContactListContext();
        $existing = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Mara', 'is_active' => true]);
        $duplicateId = $this->createAsExternal($context, 'Mara');

        Auth::shouldUse('web');
        $this->actingAsAdmin();
        $this->post(route('crm.duplicates.merge'), ['primary_id' => $existing->id, 'duplicate_ids' => [$duplicateId]])
            ->assertRedirect();

        $this->actingAs($context['external'], 'external');
        $params = $this->externalRouteParams($context, ['crmContact' => $existing->id]);
        $this->patchJson(route('external.project.tab.crm-contacts.update', $params), ['display_name' => 'Übernommen'])
            ->assertForbidden();
        $this->deleteJson(route('external.project.tab.crm-contacts.destroy', $params))->assertForbidden();
        $this->assertSame('Mara', $existing->fresh()->display_name);
    }

    #[Test]
    public function external_removal_keeps_contacts_that_are_referenced_elsewhere(): void
    {
        $context = $this->crmContactListContext();
        $contactId = $this->createAsExternal($context, 'Hotel Sonne');
        // z. B. als Unterkunft oder in einer Dokumentanfrage genutzt → darf nicht gelöscht werden
        $contact = CrmContact::query()->findOrFail($contactId);
        $contact->forceFill(['entity_type' => 'accommodation', 'entity_id' => 1])->save();

        $this->deleteJson(route(
            'external.project.tab.crm-contacts.destroy',
            $this->externalRouteParams($context, ['crmContact' => $contactId]),
        ))->assertOk();

        $this->assertNotSoftDeleted('crm_contacts', ['id' => $contactId]);
        $this->assertFalse(ProjectComponentCrmContact::query()->where('crm_contact_id', $contactId)->exists());
    }

    #[Test]
    public function link_values_must_be_http_urls(): void
    {
        $context = $this->crmContactListContext();
        $group = CrmPropertyGroup::create(['name' => 'Web', 'is_confidential' => false, 'sort_order' => 3]);
        $link = CrmProperty::create(['crm_property_group_id' => $group->id, 'name' => 'Website ' . uniqid(), 'type' => 'link']);
        $link->contactTypes()->attach($context['type']->id, ['sort_order' => 3, 'is_required' => false, 'show_in_list' => true, 'is_filterable' => false]);
        $this->actingAs($context['external'], 'external');

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Böse',
            'property_values' => [$context['phone']->id => '1', $link->id => 'javascript:alert(1)'],
        ])->assertStatus(422)->assertJsonValidationErrors(['property_values.' . $link->id]);

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Gut',
            'property_values' => [$context['phone']->id => '1', $link->id => 'https://example.test'],
        ])->assertCreated();
    }

    #[Test]
    public function editing_an_own_contact_after_review_requires_a_new_review(): void
    {
        $context = $this->crmContactListContext();
        $contactId = $this->createAsExternal($context, 'Nora');
        ProjectComponentCrmContact::query()->where('crm_contact_id', $contactId)->update(['reviewed_at' => now()]);

        $this->patchJson(route(
            'external.project.tab.crm-contacts.update',
            $this->externalRouteParams($context, ['crmContact' => $contactId]),
        ), ['display_name' => 'Nora neu', 'property_values' => [$context['phone']->id => '2']])->assertOk();

        $this->assertNull(ProjectComponentCrmContact::query()->where('crm_contact_id', $contactId)->value('reviewed_at'));
    }

    #[Test]
    public function inviting_a_tab_only_person_from_the_contact_page_binds_the_access_to_the_contact(): void
    {
        Notification::fake();
        $context = $this->crmContactListContext();
        $contact = CrmContact::create(['crm_contact_type_id' => $context['type']->id, 'display_name' => 'Luna', 'is_active' => true]);

        $access = app(ExternalAccessService::class)->invite(new InviteExternalCommand(
            email: $context['external']->email,
            crmContactTypeId: null,
            source: InviteSource::CRM_CONTACT,
            sourceReferenceProjectId: null,
            invitedBy: User::factory()->create(),
            crmAccessExpiresAt: null,
            crmContactId: $contact->id,
        ));

        $this->assertSame($context['external']->id, $access->id);
        $this->assertSame($contact->id, (int) $access->fresh()->crm_contact_id);
        $this->assertTrue($access->fresh()->isCrmAccessActive());
    }

    #[Test]
    public function tab_only_access_never_counts_as_crm_access_and_cannot_be_extended(): void
    {
        $context = $this->crmContactListContext();
        $context['external']->forceFill(['crm_access_expires_at' => now()->addMonth()])->save();
        $this->assertFalse($context['external']->fresh()->isCrmAccessActive());

        Auth::shouldUse('web');
        $this->actingAsAdmin();
        $this->patch(route('crm.external-access.extend-crm-access', $context['external']->id), [
            'expires_at' => now()->addMonths(2)->toDateString(),
        ])->assertStatus(422);
    }

    #[Test]
    public function inviting_again_reopens_a_submitted_tab(): void
    {
        $context = $this->crmContactListContext();
        $context['scope']->forceFill(['submission_status' => ExternalTabSubmissionStatus::CONFIRMED])->save();

        app(ExternalAccessScopeRepository::class)->addOrUpdateScope(
            externalAccess: $context['external'],
            projectId: $context['project']->id,
            projectTabId: $context['tab']->id,
            accessType: ExternalAccessType::WRITE,
            validFrom: now(),
            validTo: now()->addMonth(),
            grantedByUserId: $context['inviter']->id,
        );

        $this->assertSame(ExternalTabSubmissionStatus::OPEN, $context['scope']->fresh()->submission_status);
    }

    #[Test]
    public function internal_users_without_crm_access_cannot_edit_contacts_used_elsewhere(): void
    {
        $context = $this->crmContactListContext();
        $shared = $this->preLinkedContact($context, 'Geteilt');
        $otherProject = \Artwork\Modules\Project\Models\Project::factory()->create();
        $otherProject->crmContacts()->attach($shared->id);
        $writer = $this->actingAsUserWith([]);
        $context['project']->users()->attach($writer->id, ['can_write' => true]);

        $this->getJson(route('projects.components.crm-contacts.index', ['project' => $context['project']->id, 'component' => $context['component']->id]))
            ->assertOk()
            ->assertJsonPath('contacts.0.can_edit', false)
            ->assertJsonPath('contacts.0.can_remove', true);
    }

    #[Test]
    public function crm_invitation_for_a_tab_only_person_creates_the_own_contact(): void
    {
        Notification::fake();
        $context = $this->crmContactListContext();

        $access = app(ExternalAccessService::class)->invite(new InviteExternalCommand(
            email: $context['external']->email,
            crmContactTypeId: $context['type']->id,
            source: InviteSource::CRM_INDEX,
            sourceReferenceProjectId: null,
            invitedBy: User::factory()->create(),
            crmAccessExpiresAt: null,
            confidentialFieldValues: [(string) $context['iban']->id => 'DE00 1234'],
            publicFieldValues: ['display_name' => 'Luna Selbstpflege'],
        ));

        $access = $access->fresh();
        $this->assertNotNull($access->crm_contact_id);
        $this->assertTrue($access->isCrmAccessActive());
    }
}
