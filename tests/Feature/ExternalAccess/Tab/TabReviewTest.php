<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabReviewResultNotification;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class TabReviewTest extends TestCase
{
    use CrmContactListFixtures;

    protected function tearDown(): void
    {
        Auth::shouldUse('web');
        parent::tearDown();
    }

    /**
     * Externe Person legt einen Kontakt an und sendet ab.
     *
     * @return array<string, mixed>
     */
    private function submittedContext(): array
    {
        Notification::fake();
        $context = $this->crmContactListContext();
        $textField = Component::create(['name' => 'Titel', 'type' => 'TextField', 'data' => []]);
        ComponentInTab::create(['project_tab_id' => $context['tab']->id, 'component_id' => $textField->id, 'order' => 1]);
        $context['textField'] = $textField;

        $this->actingAs($context['external'], 'external');
        $context['contactId'] = $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Mara',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated()->json('contact.id');

        $this->post(route('external.project.tab.submit', [$context['project']->id, $context['tab']->id]))->assertRedirect();

        return $context;
    }

    private function actAsInternal(User $user): void
    {
        Auth::shouldUse('web');
        $this->actingAs($user, 'web');
    }

    private function reviewRoute(string $name, array $context): string
    {
        return route($name, [
            'project' => $context['project']->id,
            'projectTab' => $context['tab']->id,
            'scope' => $context['scope']->id,
        ]);
    }

    #[Test]
    public function submitting_locks_the_tab_for_the_external_person(): void
    {
        $context = $this->submittedContext();

        $this->assertSame(ExternalTabSubmissionStatus::SUBMITTED, $context['scope']->fresh()->submission_status);

        $this->patchJson(route('external.project.tab.component.update', [
            $context['project']->id,
            $context['tab']->id,
            $context['textField']->id,
        ]), ['data' => ['text' => 'Nachträglich']])->assertStatus(423);

        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Nachzügler',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertStatus(423);

        // Lesen bleibt möglich
        $this->getJson(route('external.project.tab.crm-contacts.index', $this->externalRouteParams($context)))
            ->assertOk()
            ->assertJsonPath('contacts.0.can_edit', false);
    }

    #[Test]
    public function project_writer_confirms_and_contacts_become_reviewed(): void
    {
        $context = $this->submittedContext();
        $writer = User::factory()->create();
        $context['project']->users()->attach($writer->id, ['can_write' => true]);
        $this->actAsInternal($writer);

        $this->postJson($this->reviewRoute('projects.tabs.externals.confirm', $context))
            ->assertOk()
            ->assertJsonPath('external.status', 'confirmed')
            ->assertJsonPath('external.name', 'Luna Gastspiel');

        $scope = $context['scope']->fresh();
        $this->assertSame(ExternalTabSubmissionStatus::CONFIRMED, $scope->submission_status);
        $this->assertSame($writer->id, (int) $scope->reviewed_by_user_id);
        $this->assertTrue($scope->isLockedForExternal());

        $entry = ProjectComponentCrmContact::query()->where('crm_contact_id', $context['contactId'])->firstOrFail();
        $this->assertNotNull($entry->reviewed_at);
        $this->assertSame($writer->id, (int) $entry->reviewed_by_user_id);

        Notification::assertSentTo($context['external'], ExternalTabReviewResultNotification::class);
    }

    #[Test]
    public function returning_unlocks_the_tab_and_informs_the_external_person(): void
    {
        $context = $this->submittedContext();
        $this->actAsInternal($context['inviter']);

        $this->postJson($this->reviewRoute('projects.tabs.externals.return', $context), ['comment' => 'Bitte Telefonnummern ergänzen'])
            ->assertOk()
            ->assertJsonPath('external.status', 'returned')
            ->assertJsonPath('external.review_comment', 'Bitte Telefonnummern ergänzen');

        Notification::assertSentTo(
            $context['external'],
            ExternalTabReviewResultNotification::class,
            fn (ExternalTabReviewResultNotification $notification) => $notification->scope->review_comment === 'Bitte Telefonnummern ergänzen',
        );

        $this->actingAs($context['external'], 'external');
        $this->postJson(route('external.project.tab.crm-contacts.store', $this->externalRouteParams($context)), [
            'crm_contact_type_id' => $context['type']->id,
            'display_name' => 'Nachzügler',
            'property_values' => [$context['phone']->id => '1'],
        ])->assertCreated();
    }

    #[Test]
    public function users_without_project_write_permission_cannot_review(): void
    {
        $context = $this->submittedContext();
        $reader = User::factory()->create();
        $context['project']->users()->attach($reader->id, ['can_write' => false]);
        $this->actAsInternal($reader);

        $this->postJson($this->reviewRoute('projects.tabs.externals.confirm', $context))->assertForbidden();
        $this->assertSame(ExternalTabSubmissionStatus::SUBMITTED, $context['scope']->fresh()->submission_status);

        // Status sehen darf, wer das Projekt sieht
        $this->getJson(route('projects.tabs.externals.index', ['project' => $context['project']->id, 'projectTab' => $context['tab']->id]))
            ->assertOk()
            ->assertJsonPath('externals.0.status', 'submitted')
            ->assertJsonPath('externals.0.can_review', false);
    }

    #[Test]
    public function data_that_was_not_submitted_cannot_be_confirmed(): void
    {
        Notification::fake();
        $context = $this->crmContactListContext();
        $this->actAsInternal($context['inviter']);

        $this->postJson($this->reviewRoute('projects.tabs.externals.confirm', $context))->assertStatus(422);
    }

    #[Test]
    public function scope_of_another_tab_is_rejected(): void
    {
        $context = $this->submittedContext();
        $other = $this->crmContactListContext();
        $this->actAsInternal($context['inviter']);

        $this->postJson(route('projects.tabs.externals.confirm', [
            'project' => $context['project']->id,
            'projectTab' => $context['tab']->id,
            'scope' => $other['scope']->id,
        ]))->assertNotFound();
    }
}
