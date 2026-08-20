<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoExtrasPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;

/**
 * Materialausgaben: interne Ausgaben an Demo-Projekte (Zeitraum = erster bis
 * letzter Termin, Artikel passend, Verantwortliche aus dem Team, eine
 * Sonderposition) und externe Leihgaben — eine davon überfällig, damit das
 * Rückgabe-Erinnerungs-Feature sichtbar ist.
 */
class DemoMaterialIssueSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    public function run(): void
    {
        $context = new DemoContext();
        $random = new DemoRandom('material-issues');
        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();

        $articles = InventoryArticle::query()->get();
        if ($articles->isEmpty()) {
            $this->command?->warn('Keine Inventarartikel vorhanden – Materialausgaben übersprungen.');

            return;
        }

        $this->seedInternalIssues($context, $random, $articles, $windowStart, $windowEnd);
        $this->seedRecurringIssues($context, $random, $articles, $windowStart);
        $this->seedExternalIssues($context, $random, $articles, $windowStart);
    }

    /**
     * Zusätzliche kurze Ausgaben je Monat (Probenbetrieb, Wartung) — sie
     * verdichten die Artikeldispo, damit Verfügbarkeits-Balken sichtbar sind.
     */
    private function seedRecurringIssues(
        DemoContext $context,
        DemoRandom $random,
        $articles,
        Carbon $windowStart
    ): void {
        $storageRoomId = $context->roomByName('Lager Technik')?->id;
        $created = 0;

        for ($i = 0; $i < $this->months; $i++) {
            $month = $windowStart->copy()->addMonths($i);
            foreach (['Probenbetrieb Requisiten', 'Werkstatt & Wartung'] as $label) {
                $name = sprintf('%s %s', $label, $month->format('m/Y'));
                if (InternalIssue::query()->where('name', $name)->exists()) {
                    continue;
                }
                $rng = $random->fork('recurring|' . $name);
                $start = $month->copy()->startOfMonth()->addDays($rng->int(2, 18));

                $issue = InternalIssue::create([
                    'name' => $name,
                    'project_id' => null,
                    'start_date' => $start->toDateString(),
                    'start_time' => '09:00',
                    'end_date' => $start->copy()->addDays($rng->int(3, 7))->toDateString(),
                    'end_time' => '17:00',
                    'room_id' => $storageRoomId,
                    'notes' => 'Laufende Ausgabe ohne Projektbezug (Demo).',
                    'special_items_done' => false,
                ]);
                $created++;

                $issueArticles = [];
                foreach ($rng->pickMany($articles->all(), $rng->int(2, 4)) as $article) {
                    $issueArticles[$article->id] = [
                        'quantity' => max(1, $rng->int(1, min(4, (int) $article->quantity))),
                    ];
                }
                $issue->articles()->sync($issueArticles);
                $issue->responsibleUsers()->sync([$context->plannerUser()->id]);
            }
        }

        if ($created > 0) {
            $this->command?->info(sprintf('Materialausgaben: %d laufende Kurz-Ausgaben ergänzt.', $created));
        }
    }

    private function seedInternalIssues(
        DemoContext $context,
        DemoRandom $random,
        $articles,
        Carbon $windowStart,
        Carbon $windowEnd
    ): void {
        $demoProjects = Project::query()
            ->get()
            ->filter(static function (Project $project) {
                $archetype = DemoProjectPools::archetypeForProjectName($project->name);

                return in_array($archetype, ['eigenproduktion', 'gastspiel', 'konzert'], true);
            });

        $created = 0;
        foreach ($demoProjects as $project) {
            $rng = $random->fork('internal|' . $project->name);
            if (!$rng->chance(0.85)) {
                continue;
            }

            $firstEvent = $project->events()->min('start_time');
            $lastEvent = $project->events()->max('end_time');
            if ($firstEvent === null || Carbon::parse($firstEvent)->lt($windowStart) || Carbon::parse($firstEvent)->gt($windowEnd)) {
                continue;
            }

            $name = 'Materialausgabe: ' . $project->name;
            if (InternalIssue::query()->where('name', $name)->exists()) {
                continue;
            }

            $issue = InternalIssue::create([
                'name' => $name,
                'project_id' => $project->id,
                'start_date' => Carbon::parse($firstEvent)->toDateString(),
                'start_time' => '08:00',
                'end_date' => Carbon::parse($lastEvent)->toDateString(),
                'end_time' => '18:00',
                'room_id' => $project->events()->whereNotNull('room_id')->orderBy('start_time')->value('room_id'),
                'notes' => 'Material für den gesamten Produktionszeitraum, Rückgabe nach Abbau.',
                'special_items_done' => false,
            ]);
            $created++;

            $issueArticles = [];
            foreach ($rng->pickMany($articles->all(), $rng->int(3, 6)) as $article) {
                $issueArticles[$article->id] = ['quantity' => max(1, $rng->int(1, min(6, (int) $article->quantity)))];
            }
            $issue->articles()->sync($issueArticles);

            $team = $project->users()->pluck('users.id')->all();
            $responsible = $team !== [] ? $rng->pick($team) : $context->plannerUser()->id;
            $issue->responsibleUsers()->sync([$responsible]);

            if ($rng->chance(0.3)) {
                $issue->specialItems()->create([
                    'name' => 'Leihgabe Staatsoper: 2× Verfolger',
                    'quantity' => 2,
                    'description' => 'Extern geliehen, Rückgabe direkt nach der Derniere.',
                ]);
            }
        }

        $this->command?->info(sprintf('Materialausgaben: %d interne Ausgaben angelegt.', $created));
    }

    private function seedExternalIssues(DemoContext $context, DemoRandom $random, $articles, Carbon $windowStart): void
    {
        $created = 0;
        foreach (DemoExtrasPools::EXTERNAL_ISSUES as $definition) {
            if (ExternalIssue::query()->where('name', $definition['name'])->exists()) {
                continue;
            }
            $rng = $random->fork('external|' . $definition['name']);

            $issueDate = Carbon::now()->subDays($rng->int(20, 40));
            $returnDate = $definition['overdue']
                ? Carbon::now()->subDays($rng->int(2, 8))
                : Carbon::now()->addDays($rng->int(10, 30));

            $issue = ExternalIssue::create([
                'name' => $definition['name'],
                'external_name' => $definition['external_name'],
                'external_email' => $definition['external_email'],
                'external_phone' => $definition['external_phone'],
                'external_address' => $definition['external_address'],
                'material_value' => $definition['material_value'],
                'issued_by_id' => $context->plannerUser()->id,
                'received_by_id' => $context->adminUser()->id,
                'issue_date' => $issueDate->toDateString(),
                'return_date' => $returnDate->toDateString(),
                'special_items_done' => false,
            ]);
            $created++;

            $issueArticles = [];
            foreach ($rng->pickMany($articles->all(), $rng->int(2, 4)) as $article) {
                $issueArticles[$article->id] = ['quantity' => max(1, $rng->int(1, min(4, (int) $article->quantity)))];
            }
            $issue->articles()->sync($issueArticles);
        }

        $this->command?->info(sprintf(
            'Materialausgaben: %d externe Leihgaben angelegt (davon 1 überfällig für die Erinnerungs-Demo).',
            $created
        ));
    }
}
