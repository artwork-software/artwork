<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Unterpositionen festschreiben, Festschreibung aufheben und als geprüft markieren:
 * Zellwerte werden in verified_value übernommen bzw. geleert, Budgetberechtigte des
 * Projekts der Position werden benachrichtigt; unbekannte Positionen und Vorlagen ohne
 * Projekt führen nicht zu einem 500er.
 */
final class SubPositionFixAndVerifyTest extends FeatureTestCase
{
    private Project $project;

    private SubPosition $subPosition;

    private ColumnCell $cell;

    private User $budgetUser;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Notification::swap(new ChannelManager($this->app));

        $this->project = Project::factory()->create();
        [$this->subPosition, $this->cell] = $this->createSubPositionWithCell($this->project->id, false);

        $admin = $this->actingAsAdmin();
        $this->budgetUser = User::factory()->create();
        $this->project->users()->attach($this->budgetUser->id, ['access_budget' => true]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($admin);
        app(NotificationSettingService::class)->ensureDefaultsForUser($this->budgetUser);
    }

    /**
     * @return array{0: SubPosition, 1: ColumnCell}
     */
    private function createSubPositionWithCell(?int $projectId, bool $isTemplate): array
    {
        $table = Table::factory()->create(['project_id' => $projectId, 'is_template' => $isTemplate]);
        $column = Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $cell = ColumnCell::factory()->create([
            'column_id' => $column->id,
            'sub_position_row_id' => $row->id,
            'value' => '125,50',
            'verified_value' => null,
        ]);

        return [$subPosition, $cell];
    }

    #[Test]
    public function fixing_a_sub_position_copies_cell_values_and_notifies_budget_users(): void
    {
        $this->patch(route('project.budget.fix.sub-position'), [
            'subPositionId' => $this->subPosition->id,
            'project_id' => $this->project->id,
        ])->assertSuccessful();

        $this->assertTrue((bool) $this->subPosition->fresh()->is_fixed);
        $this->assertSame('125,50', $this->cell->fresh()->verified_value);
        $this->assertSame(1, $this->budgetUser->notifications()->count());
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Project::class,
            'subject_id' => $this->project->id,
        ]);
    }

    #[Test]
    public function fixing_uses_the_project_of_the_sub_position_when_project_id_is_missing(): void
    {
        $this->patch(route('project.budget.fix.sub-position'), [
            'subPositionId' => $this->subPosition->id,
        ])->assertSuccessful();

        $this->assertTrue((bool) $this->subPosition->fresh()->is_fixed);
        $this->assertSame(1, $this->budgetUser->notifications()->count());
    }

    #[Test]
    public function fixing_notifies_the_project_of_the_position_not_a_mismatching_request_project(): void
    {
        $otherProject = Project::factory()->create();
        $otherUser = User::factory()->create();
        $otherProject->users()->attach($otherUser->id, ['access_budget' => true]);
        app(NotificationSettingService::class)->ensureDefaultsForUser($otherUser);

        $this->patch(route('project.budget.fix.sub-position'), [
            'subPositionId' => $this->subPosition->id,
            'project_id' => $otherProject->id,
        ])->assertSuccessful();

        $this->assertSame(1, $this->budgetUser->notifications()->count());
        $this->assertSame(0, $otherUser->notifications()->count());
    }

    #[Test]
    public function fixing_an_unknown_sub_position_returns_404(): void
    {
        $this->patch(route('project.budget.fix.sub-position'), [
            'subPositionId' => 999999999,
            'project_id' => $this->project->id,
        ])->assertNotFound();
    }

    #[Test]
    public function fixing_a_template_sub_position_works_without_project(): void
    {
        [$templateSubPosition, $templateCell] = $this->createSubPositionWithCell(null, true);

        $this->patch(route('project.budget.fix.sub-position'), [
            'subPositionId' => $templateSubPosition->id,
        ])->assertSuccessful();

        $this->assertTrue((bool) $templateSubPosition->fresh()->is_fixed);
        $this->assertSame('125,50', $templateCell->fresh()->verified_value);
    }

    #[Test]
    public function unfixing_a_sub_position_clears_verified_values_and_notifies_budget_users(): void
    {
        $this->subPosition->update(['is_fixed' => true]);
        $this->cell->update(['verified_value' => '125,50']);

        $this->patch(route('project.budget.unfix.sub-position'), [
            'subPositionId' => $this->subPosition->id,
            'project_id' => $this->project->id,
        ])->assertSuccessful();

        $this->assertFalse((bool) $this->subPosition->fresh()->is_fixed);
        $this->assertNull($this->cell->fresh()->verified_value);
        $this->assertSame(1, $this->budgetUser->notifications()->count());
    }

    #[Test]
    public function unfixing_without_project_id_or_with_unknown_position_does_not_fail_with_500(): void
    {
        $this->subPosition->update(['is_fixed' => true]);

        $this->patch(route('project.budget.unfix.sub-position'), [
            'subPositionId' => $this->subPosition->id,
        ])->assertSuccessful();
        $this->assertFalse((bool) $this->subPosition->fresh()->is_fixed);

        $this->patch(route('project.budget.unfix.sub-position'), [
            'subPositionId' => 999999999,
        ])->assertNotFound();
    }

    #[Test]
    public function verifying_a_sub_position_closes_it_and_copies_cell_values(): void
    {
        $this->patch(route('project.budget.verified.sub-position'), [
            'subPositionId' => $this->subPosition->id,
            'project_id' => $this->project->id,
        ])->assertSuccessful();

        $this->assertSame('BUDGET_VERIFIED_TYPE_CLOSED', $this->subPosition->fresh()->is_verified);
        $this->assertSame('125,50', $this->cell->fresh()->verified_value);
    }

    #[Test]
    public function verifying_without_project_id_or_on_a_template_does_not_fail_with_500(): void
    {
        $this->patch(route('project.budget.verified.sub-position'), [
            'subPositionId' => $this->subPosition->id,
        ])->assertSuccessful();
        $this->assertSame('BUDGET_VERIFIED_TYPE_CLOSED', $this->subPosition->fresh()->is_verified);

        [$templateSubPosition] = $this->createSubPositionWithCell(null, true);
        $this->patch(route('project.budget.verified.sub-position'), [
            'subPositionId' => $templateSubPosition->id,
        ])->assertSuccessful();
        $this->assertSame('BUDGET_VERIFIED_TYPE_CLOSED', $templateSubPosition->fresh()->is_verified);
    }

    #[Test]
    public function verifying_an_unknown_sub_position_returns_404(): void
    {
        $this->patch(route('project.budget.verified.sub-position'), [
            'subPositionId' => 999999999,
        ])->assertNotFound();
    }
}
