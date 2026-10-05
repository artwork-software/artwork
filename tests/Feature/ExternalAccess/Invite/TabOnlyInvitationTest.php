<?php

namespace Tests\Feature\ExternalAccess\Invite;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Einladung aus dem Projekt-Tab: nur E-Mail, optionaler Name und Tabs — kein eigener CRM-Kontakt,
 * keine CRM-Selbstpflege.
 */
final class TabOnlyInvitationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function tabPayload(Project $project, ProjectTab $tab, array $overrides = []): array
    {
        return array_merge([
            'email' => 'Gast-' . uniqid() . '@Example.test',
            'name' => 'Compagnie Luna',
            'source' => InviteSource::PROJECT_TAB->value,
            'source_reference_project_id' => $project->id,
            'tab_scopes' => [[
                'project_tab_id' => $tab->id,
                'access_type' => ExternalAccessType::WRITE->value,
                'valid_from' => now()->toDateString(),
                'valid_to' => now()->addMonth()->toDateString(),
            ]],
        ], $overrides);
    }

    #[Test]
    public function invitation_from_tab_creates_access_without_crm_contact(): void
    {
        Notification::fake();
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $contactsBefore = CrmContact::query()->count();

        $payload = $this->tabPayload($project, $tab);
        $this->postJson(route('crm.externals.invitations.store'), $payload)->assertCreated();

        $access = ExternalAccess::query()->where('email', mb_strtolower($payload['email']))->firstOrFail();
        $this->assertNull($access->crm_contact_id);
        $this->assertNull($access->crm_access_expires_at);
        $this->assertFalse($access->isCrmAccessActive());
        $this->assertSame('Compagnie Luna', $access->name);
        $this->assertSame('Compagnie Luna', $access->displayName());
        $this->assertSame($contactsBefore, CrmContact::query()->count());
        $this->assertTrue($access->scopes()->where('project_tab_id', $tab->id)->exists());
        $this->assertTrue($access->hasAnyActiveAccess());
    }

    #[Test]
    public function inviting_the_same_address_again_reuses_the_access(): void
    {
        Notification::fake();
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $otherTab = ProjectTab::factory()->create();

        $payload = $this->tabPayload($project, $tab, ['email' => 'wieder@example.test']);
        $this->postJson(route('crm.externals.invitations.store'), $payload)->assertCreated();
        $this->postJson(route('crm.externals.invitations.store'), $this->tabPayload($project, $otherTab, [
            'email' => 'wieder@example.test',
        ]))->assertCreated();

        $this->assertSame(1, ExternalAccess::query()->where('email', 'wieder@example.test')->count());
        $this->assertSame(2, ExternalAccess::query()->where('email', 'wieder@example.test')->firstOrFail()->scopes()->count());
    }

    #[Test]
    public function self_edit_invitation_still_requires_a_contact_type(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);

        $this->postJson(route('crm.externals.invitations.store'), [
            'email' => 'selbst@example.test',
            'source' => InviteSource::CRM_INDEX->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['crm_contact_type_id']);
    }
}
