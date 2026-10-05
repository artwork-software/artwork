<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Durchgehende Kette: Gast sendet Tab-Daten über HTTP ab → Datenbank-Meldung → Zähler und
 * Sektionsinhalt der Meldungen-Seite für die einladende Person.
 */
final class NotificationsPageShowsExternalEntriesTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::shouldUse('web');

        parent::tearDown();
    }

    #[Test]
    public function inviter_sees_tab_submission_in_external_access_group_of_notifications_page(): void
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $inviter = User::factory()->create();
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $fl->createCrmContact();
        $external = ExternalAccess::factory()->active()->create([
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create(['name' => 'Abfrage Produktion']);
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
            'access_type' => ExternalAccessType::WRITE->value,
        ]);

        // Gast sendet ab (echter HTTP-Pfad inkl. Middleware, Default-Guard „external“).
        $this->actingAs($external, 'external');
        $this->post(route('external.project.tab.submit', [$project->id, $tab->id]))->assertRedirect();

        // Einladende Person öffnet die Meldungen-Seite.
        Auth::shouldUse('web');
        $this->actingAs($inviter, 'web');

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notificationCounts.EXTERNAL_ACCESS.unread', 1)
                ->where('notificationCounts.EXTERNAL_ACCESS.archived', 0));

        $list = $this->getJson(route('notifications.list', ['groupType' => 'EXTERNAL_ACCESS']))
            ->assertOk()
            ->json();

        $this->assertCount(1, $list['data']);
        $this->assertStringContainsString('Abfrage Produktion', $list['data'][0]['data']['title']);
        $this->assertStringContainsString($project->name, $list['data'][0]['data']['title']);
        $this->assertNull($list['data'][0]['data']['created_by']);

        // In keiner anderen Gruppe taucht der Eintrag auf.
        $other = $this->getJson(route('notifications.list', ['groupType' => 'PROJECTS']))->assertOk()->json();
        $this->assertCount(0, $other['data']);
    }
}
