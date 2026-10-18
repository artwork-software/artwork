<?php

namespace Tests\Feature\ExternalAccess\Invite;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\DTOs\TabScopeInput;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Tab-Freigaben bei Einladungen: Projekt-Sichtprüfung unabhängig von der angegebenen Quelle,
 * Tab muss für die einladende Person sichtbar sein, Schreibzugriff nur mit Projekt-Schreibrecht.
 * Vorher wurde bei source != project_tab gar nichts geprüft (Schreibzugriff auf fremde Projekte).
 */
final class InvitationTabScopeAuthorizationTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function tabPayload(?Project $project, ProjectTab $tab, string $accessType, array $overrides = []): array
    {
        return array_merge([
            'email' => 'gast-' . uniqid() . '@example.test',
            'source' => InviteSource::PROJECT_TAB->value,
            'source_reference_project_id' => $project?->id,
            'tab_scopes' => [[
                'project_tab_id' => $tab->id,
                'access_type' => $accessType,
                'valid_from' => now()->toDateString(),
                'valid_to' => now()->addMonth()->toDateString(),
            ]],
        ], $overrides);
    }

    private function inviter(Project $project, ?bool $canWrite): User
    {
        $user = $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        if ($canWrite !== null) {
            $project->users()->attach($user->id, ['can_write' => $canWrite]);
        }

        return $user;
    }

    #[Test]
    public function crm_index_source_cannot_be_used_to_grant_write_access_to_a_foreign_project(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $this->inviter($project, null);
        $type = CrmContactType::query()->create(['name' => 'Sponsor', 'slug' => 'sponsor-' . uniqid()]);

        $this->postJson(route('crm.externals.invitations.store'), $this->tabPayload(
            $project,
            $tab,
            ExternalAccessType::WRITE->value,
            [
                'source' => InviteSource::CRM_INDEX->value,
                'crm_contact_type_id' => $type->id,
                'public_field_values' => ['display_name' => 'Angreifer GmbH'],
            ],
        ))->assertStatus(422)->assertJsonValidationErrors(['source_reference_project_id']);

        $this->assertSame(0, ExternalAccess::query()->count());
    }

    #[Test]
    public function tab_scopes_without_a_project_are_rejected(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $this->inviter($project, true);
        $type = CrmContactType::query()->create(['name' => 'Sponsor', 'slug' => 'sponsor-' . uniqid()]);

        $this->postJson(route('crm.externals.invitations.store'), $this->tabPayload(
            null,
            $tab,
            ExternalAccessType::READ->value,
            [
                'source' => InviteSource::CRM_INDEX->value,
                'crm_contact_type_id' => $type->id,
                'public_field_values' => ['display_name' => 'Ohne Projekt'],
            ],
        ))->assertStatus(422)->assertJsonValidationErrors(['source_reference_project_id']);

        $this->assertSame(0, ExternalAccess::query()->count());
    }

    #[Test]
    public function project_reader_may_only_grant_read_access(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $this->inviter($project, false);

        $this->postJson(
            route('crm.externals.invitations.store'),
            $this->tabPayload($project, $tab, ExternalAccessType::WRITE->value)
        )->assertStatus(422)->assertJsonValidationErrors(['tab_scopes.0.access_type']);
        $this->assertSame(0, ExternalAccess::query()->count());

        $this->postJson(
            route('crm.externals.invitations.store'),
            $this->tabPayload($project, $tab, ExternalAccessType::READ->value)
        )->assertCreated();
        $this->assertSame(1, ExternalAccess::query()->count());
    }

    #[Test]
    public function project_writer_may_grant_write_access(): void
    {
        Notification::fake();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $this->inviter($project, true);

        $payload = $this->tabPayload($project, $tab, ExternalAccessType::WRITE->value);
        $this->postJson(route('crm.externals.invitations.store'), $payload)->assertCreated();

        $access = ExternalAccess::query()->where('email', $payload['email'])->firstOrFail();
        $this->assertTrue($access->scopes()
            ->where('project_tab_id', $tab->id)
            ->where('project_id', $project->id)
            ->where('access_type', ExternalAccessType::WRITE->value)
            ->exists());
    }

    #[Test]
    public function tabs_hidden_from_the_inviter_cannot_be_shared(): void
    {
        $project = Project::factory()->create();
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $this->inviter($project, true);

        $this->postJson(
            route('crm.externals.invitations.store'),
            $this->tabPayload($project, $hiddenTab, ExternalAccessType::READ->value)
        )->assertStatus(422)->assertJsonValidationErrors(['tab_scopes.0.project_tab_id']);

        $this->assertSame(0, ExternalAccess::query()->count());
    }

    #[Test]
    public function service_refuses_tab_scopes_without_a_project(): void
    {
        $tab = ProjectTab::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(ExternalAccessService::class)->invite(new InviteExternalCommand(
            email: 'ohne-projekt@example.test',
            crmContactTypeId: null,
            source: InviteSource::CRM_INDEX,
            sourceReferenceProjectId: null,
            invitedBy: User::factory()->create(),
            crmAccessExpiresAt: null,
            tabScopes: [new TabScopeInput(
                projectTabId: $tab->id,
                accessType: ExternalAccessType::WRITE,
                validFrom: CarbonImmutable::now(),
                validTo: CarbonImmutable::now()->addMonth(),
            )],
        ));
    }
}
