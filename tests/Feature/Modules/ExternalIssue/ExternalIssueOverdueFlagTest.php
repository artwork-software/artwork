<?php

namespace Tests\Feature\Modules\ExternalIssue;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Externe Ausgaben bleiben bis zur bestätigten Rückgabe reserviert; die Übersicht zeigt das über
 * overdue_unreturned („Rückgabe offen – Material blockiert“).
 */
final class ExternalIssueOverdueFlagTest extends TestCase
{
    /**
     * @param array<string, mixed> $attributes
     */
    private function issue(array $attributes): ExternalIssue
    {
        return ExternalIssue::factory()->create(array_merge([
            'issue_date' => Carbon::today()->subDays(10)->toDateString(),
            'return_date' => Carbon::today()->subDays(2)->toDateString(),
            'return_status' => null,
            'received_by_id' => null,
        ], $attributes))->fresh();
    }

    #[Test]
    public function overdue_issue_without_confirmed_return_is_flagged(): void
    {
        $this->assertTrue($this->issue([])->toArray()['overdue_unreturned']);
        $this->assertTrue(
            $this->issue(['return_status' => ExternalIssue::RETURN_STATUS_NOT_RETURNED])->overdue_unreturned
        );
    }

    #[Test]
    public function returned_or_not_yet_due_issues_are_not_flagged(): void
    {
        $returned = $this->issue(['return_status' => ExternalIssue::RETURN_STATUS_RETURNED]);
        $this->assertFalse($returned->overdue_unreturned);
        // „Erhalten von“ ausgefüllt zählt als zurückgegeben
        $this->assertFalse($this->issue(['received_by_id' => User::factory()->create()->id])->overdue_unreturned);
        // heute fällig ist noch nicht überfällig
        $this->assertFalse($this->issue(['return_date' => Carbon::today()->toDateString()])->overdue_unreturned);
    }

    #[Test]
    public function overview_delivers_the_flag(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);
        $overdue = $this->issue(['name' => 'Überfällig']);

        $this->get(route('extern-issue-of-material.index', ['overdue_only' => 1]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('issues.data.0.id', $overdue->id)
                ->where('issues.data.0.overdue_unreturned', true));
    }
}
