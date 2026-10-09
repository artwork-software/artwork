<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Notifications\InventoryArticleNotification;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Überbuchungsprüfung beim Speichern einer Ausgabe lädt Artikel und Ausgaben einmal und wertet
 * je Ausgabe im Speicher aus. Vorher je Ausgabe fresh(), zwei Overlap-Queries, Statuswerte und lazy
 * geladene Verantwortliche – bei vielen künftigen Ausgaben tausende Queries bis zum Timeout.
 */
final class InventoryOverbookingCheckTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 09:00'));
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
    public function internal_and_external_issues_are_checked_together(): void
    {
        $article = InventoryArticle::factory()->create(['quantity' => 2, 'is_detailed_quantity' => false]);
        $planner = User::factory()->create(['language' => 'de']);
        $lender = User::factory()->create(['language' => 'de']);
        $unaffected = User::factory()->create(['language' => 'de']);

        $this->internalIssue($article, '2026-11-10', '10:00', '12:00', 2)
            ->responsibleUsers()->attach($planner->id);
        $loan = ExternalIssue::factory()->create([
            'issue_date' => '2026-11-10',
            'return_date' => '2026-11-11',
            'issued_by_id' => $lender->id,
        ]);
        $loan->articles()->attach($article->id, ['quantity' => 1]);
        // Später, ohne Überschneidung: keine Meldung
        $this->internalIssue($article, '2026-11-20', '10:00', '12:00', 2)
            ->responsibleUsers()->attach($unaffected->id);

        app(InventoryArticleService::class)->checkAndNotifyOverbooking($article);

        Notification::assertSentToTimes($planner, InventoryArticleNotification::class, 1);
        Notification::assertSentToTimes($lender, InventoryArticleNotification::class, 1);
        Notification::assertNotSentTo($unaffected, InventoryArticleNotification::class);
    }

    #[Test]
    public function finished_issues_overlapping_the_start_of_a_future_issue_still_count(): void
    {
        $article = InventoryArticle::factory()->create(['quantity' => 3, 'is_detailed_quantity' => false]);
        $planner = User::factory()->create(['language' => 'de']);

        // Schon beendet (vor „heute“), überschneidet sich aber mit dem Beginn der laufenden Ausgabe
        $finished = InternalIssue::factory()->create([
            'start_date' => '2026-10-20', 'start_time' => '00:00', 'end_date' => '2026-10-31', 'end_time' => '23:59',
        ]);
        $finished->articles()->attach($article->id, ['quantity' => 2]);
        $running = InternalIssue::factory()->create([
            'start_date' => '2026-10-25', 'start_time' => '08:00', 'end_date' => '2026-11-03', 'end_time' => '16:00',
        ]);
        $running->articles()->attach($article->id, ['quantity' => 2]);
        $running->responsibleUsers()->attach($planner->id);

        app(InventoryArticleService::class)->checkAndNotifyOverbooking($article);

        Notification::assertSentToTimes($planner, InventoryArticleNotification::class, 1);
    }

    #[Test]
    public function the_number_of_queries_does_not_grow_with_the_number_of_issues(): void
    {
        $article = InventoryArticle::factory()->create(['quantity' => 100, 'is_detailed_quantity' => false]);
        for ($day = 2; $day <= 26; $day++) {
            $issue = $this->internalIssue($article, sprintf('2026-11-%02d', $day), '10:00', '12:00', 1);
            $issue->responsibleUsers()->attach(User::factory()->create()->id);
            $loan = ExternalIssue::factory()->create([
                'issue_date' => sprintf('2026-11-%02d', $day),
                'return_date' => sprintf('2026-11-%02d', $day),
                'issued_by_id' => User::factory()->create()->id,
            ]);
            $loan->articles()->attach($article->id, ['quantity' => 1]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(InventoryArticleService::class)->checkAndNotifyOverbooking($article);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Notification::assertNothingSent();
        // fresh, je zwei Ausgaben-/Eager-Queries für künftige und berührte Ausgaben, Statuswerte,
        // Standard-Status – unabhängig von 50 Ausgaben (vorher > 200 Queries)
        $this->assertLessThanOrEqual(12, $queryCount);
    }
}
