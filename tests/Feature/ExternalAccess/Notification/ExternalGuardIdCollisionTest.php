<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Notifications\ExternalCrmSubmissionNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalNotificationSender;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Regression: Im Gastkontext ist der Default-Guard „external“. Der NotificationService verglich
 * die Empfänger-User-ID mit Auth::id() — also mit der ExternalAccess-ID. Bei gleicher ID (z. B. beide 1)
 * wurde die Benachrichtigung an die einladende Person still verschluckt.
 */
final class ExternalGuardIdCollisionTest extends TestCase
{
    /**
     * @return array{0:ExternalAccess,1:User}
     */
    private function externalActingAsGuestWithSameIdAsInviter(): array
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $inviter = User::factory()->create();
        $fl = Freelancer::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $fl->createCrmContact();

        $external = ExternalAccess::factory()->create([
            'id' => $inviter->id,
            'crm_contact_id' => $fl->crmContact()->firstOrFail()->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        // Wie die Middleware Authenticate des Moduls: Gast einloggen und Default-Guard umschalten.
        Auth::guard('external')->login($external);
        Auth::shouldUse('external');

        $this->assertSame($inviter->id, Auth::id(), 'Vorbedingung: IDs kollidieren');

        return [$external, $inviter];
    }

    protected function tearDown(): void
    {
        Auth::shouldUse('web');

        parent::tearDown();
    }

    #[Test]
    public function tab_submission_notifies_inviter_even_if_external_id_equals_user_id(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalActingAsGuestWithSameIdAsInviter();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        app(ExternalNotificationSender::class)->notifyTabSubmitted($external, $project, $tab, 2);

        Notification::assertSentTo(
            $inviter,
            ExternalTabComponentUpdatedNotification::class,
            function (ExternalTabComponentUpdatedNotification $notification): bool {
                // Im Gastkontext gibt es keine handelnde interne Person — das ExternalAccess-Modell
                // darf nicht als created_by in den Meldungsdaten landen (kaputte Avatare im Frontend).
                $this->assertNull($notification->toArray()->created_by);

                return true;
            },
        );
    }

    #[Test]
    public function crm_submission_notifies_inviter_even_if_external_id_equals_user_id(): void
    {
        Notification::fake();
        [$external, $inviter] = $this->externalActingAsGuestWithSameIdAsInviter();

        app(ExternalSelfEditSubmissionService::class)->submit($external, ['personal' => ['zip_code' => '10117']]);

        Notification::assertSentTo($inviter, ExternalCrmSubmissionNotification::class);
    }
}
