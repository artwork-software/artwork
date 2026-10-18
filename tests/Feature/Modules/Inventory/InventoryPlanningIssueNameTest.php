<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryPlanningService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Titel externer Ausgaben im Verfügbarkeitskalender: Name, sonst Empfänger, sonst „Leihschein #id“ –
 * leere Werte zählen als nicht gesetzt.
 */
final class InventoryPlanningIssueNameTest extends FeatureTestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function issuesFor(ExternalIssue $issue): array
    {
        $issue->articles()->attach(InventoryArticle::factory()->create()->id, ['quantity' => 1]);
        $service = app(InventoryPlanningService::class);
        $collect = fn ($external) => $this->collectIssuesForRange(new InternalIssue()->newCollection(), $external);

        return $collect->call($service, $issue->newCollection([$issue->fresh('articles')]))['issues'];
    }

    #[Test]
    public function an_empty_receiver_falls_back_to_the_loan_slip_number(): void
    {
        $issue = ExternalIssue::factory()->create(['name' => null, 'external_name' => '']);

        $this->assertSame('Leihschein #' . $issue->id, $this->issuesFor($issue)[0]['name']);
    }

    #[Test]
    public function the_receiver_is_used_when_the_issue_has_no_name(): void
    {
        $issue = ExternalIssue::factory()->create(['name' => '', 'external_name' => 'Theater Nord']);

        $this->assertSame('Theater Nord', $this->issuesFor($issue)[0]['name']);
    }
}
