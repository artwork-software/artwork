<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Prüfanfragen im Budget: die Benachrichtigung an die prüfende Person verschwindet beim Prüfen
 * bzw. Zurückziehen (vorher Filter auf die falsche Person → blieb für immer im Posteingang), und
 * die Rücknahme erreicht die jeweils andere Seite.
 */
final class BudgetVerificationNotificationTest extends FeatureTestCase
{
    private User $requester;

    private User $verifier;

    private MainPosition $position;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Notification::swap(new ChannelManager($this->app));
        $this->project = Project::factory()->create();
        $table = Table::factory()->create(['is_template' => false, 'project_id' => $this->project->id]);
        Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $this->position = MainPosition::factory()->create(['table_id' => $table->id]);
        $this->requester = $this->actingAsAdmin();
        $this->verifier = User::factory()->create();
        // Prüfende haben Budgetzugriff im Projekt (beim Anfragen per giveBudgetAccess vergeben)
        $this->project->users()->attach($this->verifier->id, ['access_budget' => true]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($this->requester);
        app(NotificationSettingService::class)->ensureDefaultsForUser($this->verifier);

        $this->post(route('project.budget.verified.main-position.request'), [
            'id' => $this->position->id,
            'user' => $this->verifier->id,
        ])->assertSuccessful();
        $this->assertSame(1, $this->verifier->notifications()->count());
    }

    #[Test]
    public function verifying_removes_the_request_notification(): void
    {
        $this->patch(route('project.budget.verified.main-position'), [
            'mainPositionId' => $this->position->id,
            'project_id' => $this->project->id,
        ])
            ->assertSuccessful();

        $this->assertSame(0, $this->verifier->notifications()->count());
    }

    #[Test]
    public function withdrawing_by_the_verifier_informs_the_requester(): void
    {
        $this->actingAs($this->verifier);
        $this->verifier->givePermissionTo('can add and remove verified states');

        $this->post(route('project.budget.remove.verification'), [
            'type' => 'main',
            'position' => ['id' => $this->position->id],
        ])->assertSuccessful();

        $this->assertSame(0, $this->verifier->notifications()->count());
        $removed = $this->requester->notifications()->sole()->data;
        $this->assertSame(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED->value, $removed['type']);
    }
}
