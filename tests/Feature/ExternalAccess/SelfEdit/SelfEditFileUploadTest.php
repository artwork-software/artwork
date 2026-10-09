<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditFileService;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Upload-Eigenschaften in „Meine Daten“: die Datei geht vorläufig mit der Einreichung mit und
 * landet erst mit der Freigabe am Kontakt.
 */
final class SelfEditFileUploadTest extends TestCase
{
    private CrmContactType $type;

    private CrmPropertyGroup $group;

    private CrmProperty $uploadProperty;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        $this->setUploadEnabled(true);

        $this->type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency-upload']);
        $this->group = CrmPropertyGroup::query()->create(['name' => 'Unterlagen', 'is_confidential' => false]);
        $this->uploadProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $this->group->id,
            'name' => 'Technical Rider',
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
        ]);
        $this->uploadProperty->contactTypes()->attach($this->type->id, ['is_required' => false]);
    }

    private function setUploadEnabled(bool $enabled): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->file_upload_enabled = $enabled;
        $settings->save();
    }

    private function external(?User $inviter = null): ExternalAccess
    {
        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $this->type->id,
            'display_name' => 'Agentur Nord',
            'is_active' => true,
        ]);

        return ExternalAccess::factory()->active()->create([
            'crm_contact_id' => $contact->id,
            'invited_by_user_id' => ($inviter ?? User::factory()->create())->id,
        ]);
    }

    /**
     * Nach einem Request im externen Bereich ist "external" der Standard-Guard (Auth::shouldUse);
     * intern gilt wieder der App-Standard (sanctum).
     */
    private function actingAsInternal(User $user): void
    {
        $this->actingAs($user, 'sanctum');
    }

    private function fieldKey(): string
    {
        return 'crm_property:' . $this->uploadProperty->id;
    }

    private function sectionKey(): string
    {
        return 'crm_group_' . $this->group->id;
    }

    private function submitAs(ExternalAccess $external, mixed $value): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($external, 'external');

        return $this->post(route('external.crm.submit'), [
            'values' => [$this->sectionKey() => [$this->fieldKey() => $value]],
        ]);
    }

    private function storeCurrentFile(ExternalAccess $external, string $path = 'crm-property-files/alt.pdf'): void
    {
        Storage::disk('local')->put($path, 'alt');
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $this->uploadProperty->id,
            'value' => $path,
        ]);
    }

    private function latestChange(ExternalAccess $external): ExternalPendingFieldChange
    {
        return ExternalPendingSubmission::query()
            ->where('external_access_id', $external->id)
            ->latest('id')
            ->firstOrFail()
            ->fieldChanges()
            ->firstOrFail();
    }

    #[Test]
    public function edit_page_offers_upload_property_as_file_field_with_current_file_name(): void
    {
        $external = $this->external();
        $this->storeCurrentFile($external);
        $this->actingAs($external, 'external');

        $this->get(route('external.crm.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fileUpload.enabled', true)
                ->where('fileUpload.max_kilobytes', 10240)
                ->where('schema.sections.1.fields.0.key', $this->fieldKey())
                ->where('schema.sections.1.fields.0.inputType', 'file')
                // nur der Dateiname, nie der Speicherpfad am Kontakt
                ->where('schema.sections.1.fields.0.value', 'alt.pdf'));
    }

    #[Test]
    public function uploaded_file_is_staged_with_the_submission_and_not_stored_on_the_contact(): void
    {
        $external = $this->external();

        $this->submitAs($external, UploadedFile::fake()->create('Rider 2026.pdf', 120, 'application/pdf'))
            ->assertRedirect(route('external.crm.show'));

        $change = $this->latestChange($external);
        $this->assertSame((new CrmContact())->getMorphClass(), $change->target_type);
        $this->assertSame($external->crm_contact_id, (int) $change->target_id);
        $this->assertSame('upload', $change->new_value['kind']);
        $this->assertSame('Rider 2026.pdf', $change->new_value['name']);
        $this->assertMatchesRegularExpression(
            '#^external-crm-submissions/[a-f0-9]{32}\.pdf$#',
            $change->new_value['path'],
        );
        Storage::disk('local')->assertExists($change->new_value['path']);

        $this->assertDatabaseMissing('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $this->uploadProperty->id,
        ]);
    }

    #[Test]
    public function wrong_type_and_oversized_files_are_rejected_without_storing_anything(): void
    {
        $external = $this->external();
        $errorKey = 'values.' . $this->sectionKey() . '.' . $this->fieldKey();

        $this->submitAs($external, UploadedFile::fake()->create('tool.exe', 10, 'application/x-msdownload'))
            ->assertSessionHasErrors($errorKey);
        $this->submitAs($external, UploadedFile::fake()->create('riesig.pdf', 10241, 'application/pdf'))
            ->assertSessionHasErrors($errorKey);

        $this->assertDatabaseMissing('external_pending_submissions', ['external_access_id' => $external->id]);
        $this->assertSame([], Storage::disk('local')->allFiles(ExternalSelfEditFileService::DIRECTORY));
    }

    #[Test]
    public function upload_switch_off_blocks_file_changes(): void
    {
        $this->setUploadEnabled(false);
        $external = $this->external();
        $this->storeCurrentFile($external);

        $this->submitAs($external, UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf'))
            ->assertForbidden();
        $this->submitAs($external, '')->assertForbidden();

        $this->assertDatabaseMissing('external_pending_submissions', ['external_access_id' => $external->id]);
        $this->assertSame([], Storage::disk('local')->allFiles(ExternalSelfEditFileService::DIRECTORY));

        $this->get(route('external.crm.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fileUpload.enabled', false));
    }

    #[Test]
    public function text_value_for_an_upload_field_is_never_staged(): void
    {
        $external = $this->external();

        $this->submitAs($external, 'crm-property-files/fremdedatei.pdf')->assertRedirect();

        $this->assertDatabaseMissing('external_pending_submissions', ['external_access_id' => $external->id]);
    }

    #[Test]
    public function approving_takes_over_the_file_and_deletes_the_old_one(): void
    {
        $inviter = User::factory()->create();
        $external = $this->external($inviter);
        $this->storeCurrentFile($external);
        $this->submitAs($external, UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf'));
        $change = $this->latestChange($external);
        $pendingPath = $change->new_value['path'];

        $this->actingAsInternal($inviter);
        $this->post(route('crm.contacts.external-submissions.approve-all', [
            $external->crm_contact_id,
            $change->submission_id,
        ]))->assertRedirect();

        $value = CrmPropertyValue::query()
            ->where('crm_contact_id', $external->crm_contact_id)
            ->where('crm_property_id', $this->uploadProperty->id)
            ->value('value');
        $this->assertSame('crm-property-files/' . basename($pendingPath), $value);
        Storage::disk('local')->assertExists($value);
        Storage::disk('local')->assertMissing('crm-property-files/alt.pdf');
        Storage::disk('local')->assertMissing($pendingPath);
        $this->assertSame(FieldApprovalStatus::APPROVED, $change->fresh()->approval_status);
        $this->assertSame(ExternalSubmissionStatus::APPROVED, $change->submission->fresh()->status);
    }

    #[Test]
    public function approving_a_removal_clears_the_value_and_deletes_the_file(): void
    {
        $external = $this->external();
        $this->storeCurrentFile($external);
        $this->submitAs($external, '')->assertRedirect();
        $change = $this->latestChange($external);
        $this->assertSame(['kind' => 'remove'], $change->new_value);

        app(ExternalSubmissionApprovalService::class)->approveAll($change->submission, $external->invitedBy);

        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $this->uploadProperty->id,
            'value' => null,
        ]);
        Storage::disk('local')->assertMissing('crm-property-files/alt.pdf');
    }

    #[Test]
    public function rejecting_deletes_the_provisional_file(): void
    {
        $inviter = User::factory()->create();
        $external = $this->external($inviter);
        $service = app(ExternalSubmissionApprovalService::class);

        $this->submitAs($external, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $first = $this->latestChange($external);
        $service->rejectAll($first->submission, $inviter, 'Bitte neu');
        Storage::disk('local')->assertMissing($first->new_value['path']);

        $this->submitAs($external, UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'));
        $second = $this->latestChange($external);
        $service->applyPartialDecisions($second->submission, $inviter, [$second->id => FieldApprovalStatus::REJECTED]);
        Storage::disk('local')->assertMissing($second->new_value['path']);

        $this->assertDatabaseMissing('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $this->uploadProperty->id,
        ]);
    }

    #[Test]
    public function newer_submission_deletes_the_file_of_the_superseded_one(): void
    {
        $external = $this->external();

        $this->submitAs($external, UploadedFile::fake()->create('alt.pdf', 10, 'application/pdf'));
        $older = $this->latestChange($external);
        $this->submitAs($external, UploadedFile::fake()->create('neu.pdf', 10, 'application/pdf'));
        $newer = $this->latestChange($external);

        $this->assertSame(ExternalSubmissionStatus::SUPERSEDED, $older->submission->fresh()->status);
        Storage::disk('local')->assertMissing($older->new_value['path']);
        Storage::disk('local')->assertExists($newer->new_value['path']);
    }

    #[Test]
    public function legacy_text_path_does_not_block_approve_all(): void
    {
        $inviter = User::factory()->create();
        $external = $this->external($inviter);
        $textProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $this->group->id,
            'name' => 'Notiz',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $textProperty->contactTypes()->attach($this->type->id, ['is_required' => false]);
        $submission = ExternalPendingSubmission::query()->create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);
        $legacy = ExternalPendingFieldChange::query()->create([
            'submission_id' => $submission->id,
            'target_type' => (new CrmContact())->getMorphClass(),
            'target_id' => $external->crm_contact_id,
            'field_key' => $this->fieldKey(),
            'old_value' => null,
            'new_value' => 'crm-property-files/fremdedatei.pdf',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);
        ExternalPendingFieldChange::query()->create([
            'submission_id' => $submission->id,
            'target_type' => (new CrmContact())->getMorphClass(),
            'target_id' => $external->crm_contact_id,
            'field_key' => 'crm_property:' . $textProperty->id,
            'old_value' => null,
            'new_value' => 'Hallo',
            'approval_status' => FieldApprovalStatus::PENDING,
        ]);

        $this->actingAsInternal($inviter);
        $this->get(route('crm.contacts.external-submissions.show', [$external->crm_contact_id, $submission->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('submission.field_changes.0.not_applicable_reason', 'legacy_upload')
                ->where('submission.field_changes.0.file_change', null)
                ->where('submission.field_changes.1.not_applicable_reason', null));

        $approveRoute = route('crm.contacts.external-submissions.approve-all', [
            $external->crm_contact_id,
            $submission->id,
        ]);
        $this->post($approveRoute)
            ->assertRedirect()
            ->assertSessionHas('status', __('Submission approved.') . ' ' . trans_choice(
                ':count change could not be applied (legacy entry or missing file) and was skipped.'
                . '|:count changes could not be applied (legacy entries or missing files) and were skipped.',
                1,
                ['count' => 1],
            ))
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $textProperty->id,
            'value' => 'Hallo',
        ]);
        $this->assertDatabaseMissing('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $this->uploadProperty->id,
        ]);
        $this->assertSame(FieldApprovalStatus::REJECTED, $legacy->fresh()->approval_status);
        $this->assertSame(ExternalSubmissionStatus::PARTIALLY_APPROVED, $submission->fresh()->status);
    }

    #[Test]
    public function external_can_only_stage_files_for_their_own_contact(): void
    {
        $external = $this->external();
        $otherType = CrmContactType::query()->create(['name' => 'Fremd', 'slug' => 'foreign-upload']);
        $foreignGroup = CrmPropertyGroup::query()->create(['name' => 'Fremd', 'is_confidential' => false]);
        $foreignProperty = CrmProperty::query()->create([
            'crm_property_group_id' => $foreignGroup->id,
            'name' => 'Fremde Datei',
            'type' => CrmPropertyTypeEnum::UPLOAD->value,
        ]);
        $foreignProperty->contactTypes()->attach($otherType->id, ['is_required' => false]);
        $this->actingAs($external, 'external');

        // Eigenschaft gehört nicht zum eigenen Kontakttyp: wird ignoriert, keine Datei gespeichert
        $this->post(route('external.crm.submit'), [
            'values' => ['crm_group_' . $foreignGroup->id => [
                'crm_property:' . $foreignProperty->id => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('external_pending_submissions', ['external_access_id' => $external->id]);
        $this->assertSame([], Storage::disk('local')->allFiles(ExternalSelfEditFileService::DIRECTORY));
    }

    #[Test]
    public function reviewers_can_fetch_the_proposed_file_and_others_cannot(): void
    {
        $inviter = User::factory()->create();
        $external = $this->external($inviter);
        $this->submitAs($external, UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf'));
        $change = $this->latestChange($external);
        $route = route('crm.contacts.external-submissions.proposed-file', [
            $external->crm_contact_id,
            $change->submission_id,
            $change->id,
        ]);

        $this->actingAsInternal($inviter);
        $this->get(route('crm.contacts.external-submissions.show', [$external->crm_contact_id, $change->submission_id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('submission.field_changes.0.file_change.kind', 'upload')
                ->where('submission.field_changes.0.file_change.name', 'rider.pdf')
                ->where('submission.field_changes.0.file_change.url', $route)
                ->where('submission.field_changes.0.new_value', 'rider.pdf'));
        $response = $this->get($route);
        $response->assertOk();
        $this->assertStringContainsString('rider.pdf', (string) $response->headers->get('Content-Disposition'));

        // Fremde Person: kein Prüfrecht
        $this->actingAsInternal(User::factory()->create());
        $this->get($route)->assertForbidden();

        // Andere Einreichung / anderer Kontakt: nicht auffindbar
        $other = $this->external($inviter);
        $this->actingAsInternal($inviter);
        $this->get(route('crm.contacts.external-submissions.proposed-file', [
            $other->crm_contact_id,
            $change->submission_id,
            $change->id,
        ]))->assertNotFound();
    }
}
