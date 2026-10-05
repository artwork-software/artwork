<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Services\ExternalIssueService;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Reservierungen und Überbuchung im Inventar: gleichzeitige Nutzung je Zeitraum über interne und
 * externe Ausgaben, aneinandergrenzende Ausgaben überlappen nicht, überfälliges Material bleibt
 * reserviert.
 */
final class InventoryReservationTest extends FeatureTestCase
{
    private function article(int $quantity): InventoryArticle
    {
        // ohne Statusmengen: einsatzbereit = Gesamtmenge
        return InventoryArticle::factory()->create(['quantity' => $quantity, 'is_detailed_quantity' => false]);
    }

    private function internalIssue(InventoryArticle $article, string $day, string $from, string $to, int $qty): InternalIssue
    {
        $issue = InternalIssue::factory()->create([
            'start_date' => $day,
            'start_time' => $from,
            'end_date' => $day,
            'end_time' => $to,
        ]);
        $issue->articles()->attach($article->id, ['quantity' => $qty]);

        return $issue;
    }

    #[Test]
    public function issues_that_only_touch_do_not_overlap(): void
    {
        $article = $this->article(5);
        $this->internalIssue($article, '2026-11-10', '10:00', '12:00', 3);
        $this->internalIssue($article, '2026-11-10', '12:00', '14:00', 3);

        $stock = $article->fresh()->getAvailableStock('2026-11-10', '2026-11-10');

        $this->assertSame(3, $stock['reserved']);
        $this->assertEquals(2, $stock['available']);
    }

    #[Test]
    public function only_usage_inside_the_requested_period_counts(): void
    {
        $article = $this->article(5);
        $long = InternalIssue::factory()->create([
            'start_date' => '2026-11-01', 'start_time' => '00:00', 'end_date' => '2026-11-05', 'end_time' => '23:59',
        ]);
        $long->articles()->attach($article->id, ['quantity' => 2]);
        $short = InternalIssue::factory()->create([
            'start_date' => '2026-11-01', 'start_time' => '00:00', 'end_date' => '2026-11-02', 'end_time' => '23:59',
        ]);
        $short->articles()->attach($article->id, ['quantity' => 3]);

        // Am 05.11. läuft nur noch die lange Ausgabe; die Überschneidung am 01./02. zählt nicht
        $this->assertSame(2, $article->fresh()->getAvailableStock('2026-11-05', '2026-11-05')['reserved']);
        $this->assertSame(5, $article->fresh()->getAvailableStock('2026-11-01', '2026-11-05')['reserved']);
    }

    #[Test]
    public function overdue_material_that_was_not_returned_stays_reserved(): void
    {
        $article = $this->article(4);
        $overdue = ExternalIssue::factory()->create([
            'issue_date' => Carbon::today()->subDays(10)->toDateString(),
            'return_date' => Carbon::today()->subDays(3)->toDateString(),
            'return_status' => null,
        ]);
        $overdue->articles()->attach($article->id, ['quantity' => 3]);
        $today = Carbon::today()->toDateString();

        $this->assertSame(3, $article->fresh()->getAvailableStock($today, $today)['reserved']);

        $overdue->update(['return_status' => ExternalIssue::RETURN_STATUS_RETURNED]);
        $this->assertSame(0, $article->fresh()->getAvailableStock($today, $today)['reserved']);
    }

    #[Test]
    public function issues_taken_back_before_the_return_status_existed_are_free(): void
    {
        $article = $this->article(4);
        // Altbestand: Rücknahme nur über „Erhalten von“ eingetragen, kein Rückgabe-Status
        $legacy = ExternalIssue::factory()->create([
            'issue_date' => Carbon::today()->subDays(60)->toDateString(),
            'return_date' => Carbon::today()->subDays(50)->toDateString(),
            'return_status' => null,
            'received_by_id' => User::factory()->create()->id,
        ]);
        $legacy->articles()->attach($article->id, ['quantity' => 3]);
        $today = Carbon::today()->toDateString();

        $this->assertSame(0, $article->fresh()->getAvailableStock($today, $today)['reserved']);
        $this->assertTrue($legacy->fresh()->effectiveReturnDate()->isSameDay(Carbon::today()->subDays(50)));
    }

    #[Test]
    public function overbooking_is_reported_only_for_overlapping_issues(): void
    {
        $article = $this->article(5);
        $planner = User::factory()->create(['language' => 'de']);
        $morning = $this->internalIssue($article, '2026-11-10', '10:00', '12:00', 3);
        $afternoon = $this->internalIssue($article, '2026-11-10', '12:00', '14:00', 3);
        $morning->responsibleUsers()->attach($planner->id);
        $afternoon->responsibleUsers()->attach($planner->id);
        $this->travelTo(Carbon::parse('2026-11-01 09:00'));

        // 3 + 3 > 5, aber zeitlich getrennt: kein Alarm (vorher: Summe aller künftigen Ausgaben)
        app(InventoryArticleService::class)->checkAndNotifyOverbooking($article->fresh());
        Notification::assertNothingSent();

        $overlapping = $this->internalIssue($article, '2026-11-10', '11:00', '13:00', 3);
        $overlapping->responsibleUsers()->attach($planner->id);
        app(InventoryArticleService::class)->checkAndNotifyOverbooking($article->fresh());

        // Alle drei Ausgaben überlappen mit einer anderen → je eine Meldung
        Notification::assertSentToTimes($planner, \Artwork\Modules\Inventory\Notifications\InventoryArticleNotification::class, 3);
    }

    #[Test]
    public function returning_before_the_issue_date_does_not_reverse_the_period(): void
    {
        $this->actingAsAdmin();
        $issue = ExternalIssue::factory()->create([
            'issue_date' => Carbon::today()->addDays(5)->toDateString(),
            'return_date' => Carbon::today()->addDays(9)->toDateString(),
        ]);

        app(ExternalIssueService::class)->confirmReturn($issue, null);

        $this->assertSame(
            Carbon::today()->addDays(5)->toDateString(),
            $issue->fresh()->return_date->toDateString()
        );
    }
}
