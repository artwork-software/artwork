<?php

namespace Database\Seeders;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventoryIssueTestSeeder extends Seeder
{
    public function run(): void
    {
        $articleIds = InventoryArticle::pluck('id')->toArray();
        if (empty($articleIds)) {
            $this->command->warn('Keine InventoryArticles vorhanden – Seeder übersprungen.');
            return;
        }

        $roomId = Room::value('id') ?? 1;
        $projectId = Project::value('id') ?? 1;
        $userId = User::value('id') ?? 1;

        $today = Carbon::today();

        // ---------- Internal Issues ----------

        $internalScenarios = [
            // 1) Vergangener Vorgang (abgeschlossen)
            [
                'name' => 'Vergangene Probe – Hauptraum',
                'start_date' => $today->copy()->subDays(14)->format('Y-m-d'),
                'end_date' => $today->copy()->subDays(12)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
            // 2) Aktuell laufend (heute mittendrin)
            [
                'name' => 'Laufende Produktion – Woche',
                'start_date' => $today->copy()->subDays(2)->format('Y-m-d'),
                'end_date' => $today->copy()->addDays(3)->format('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '22:00',
            ],
            // 3) Heute eintägig
            [
                'name' => 'Tagesveranstaltung heute',
                'start_date' => $today->format('Y-m-d'),
                'end_date' => $today->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '18:00',
            ],
            // 4) Zukünftig (nächste Woche)
            [
                'name' => 'Workshop nächste Woche',
                'start_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'end_date' => $today->copy()->addDays(9)->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '16:00',
            ],
            // 5) Überlappend mit #4 (Konflikt-Test)
            [
                'name' => 'Paralleler Aufbau (Überlappung)',
                'start_date' => $today->copy()->addDays(8)->format('Y-m-d'),
                'end_date' => $today->copy()->addDays(11)->format('Y-m-d'),
                'start_time' => '06:00',
                'end_time' => '14:00',
            ],
            // 6) Langer Zeitraum (Monat)
            [
                'name' => 'Langzeit-Ausleihe intern',
                'start_date' => $today->copy()->addDays(1)->format('Y-m-d'),
                'end_date' => $today->copy()->addDays(30)->format('Y-m-d'),
                'start_time' => '00:00',
                'end_time' => '23:59',
            ],
            // 7) Weit in der Zukunft
            [
                'name' => 'Langfristige Reservierung',
                'start_date' => $today->copy()->addDays(20)->format('Y-m-d'),
                'end_date' => $today->copy()->addDays(50)->format('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '18:00',
            ],
        ];

        foreach ($internalScenarios as $scenario) {
            $issue = InternalIssue::create([
                'name' => $scenario['name'],
                'project_id' => $projectId,
                'room_id' => $roomId,
                'start_date' => $scenario['start_date'],
                'start_time' => $scenario['start_time'],
                'end_date' => $scenario['end_date'],
                'end_time' => $scenario['end_time'],
                'notes' => 'Erstellt von InventoryIssueTestSeeder',
                'special_items_done' => false,
            ]);

            // Verknüpfe 1-3 zufällige Artikel mit zufälliger Menge
            $randomArticles = collect($articleIds)->shuffle()->take(rand(1, min(3, count($articleIds))));
            foreach ($randomArticles as $artId) {
                $issue->articles()->attach($artId, ['quantity' => rand(1, 5)]);
            }

            $this->command->info("  Internal: {$issue->name} ({$scenario['start_date']} → " . ($scenario['end_date'] ?? 'offen') . ")");
        }

        // ---------- External Issues ----------

        $externalScenarios = [
            // 1) Vergangene Ausgabe (zurückgegeben)
            [
                'name' => 'Verleih an Theater XY (abgeschlossen)',
                'issue_date' => $today->copy()->subDays(20)->format('Y-m-d'),
                'return_date' => $today->copy()->subDays(15)->format('Y-m-d'),
            ],
            // 2) Aktuell ausgeliehen
            [
                'name' => 'Aktueller Verleih – Firma ABC',
                'issue_date' => $today->copy()->subDays(3)->format('Y-m-d'),
                'return_date' => $today->copy()->addDays(4)->format('Y-m-d'),
            ],
            // 3) Heute ausgegeben
            [
                'name' => 'Heute ausgegeben – Messe',
                'issue_date' => $today->format('Y-m-d'),
                'return_date' => $today->copy()->addDays(2)->format('Y-m-d'),
            ],
            // 4) Zukünftige Ausgabe
            [
                'name' => 'Geplanter Verleih nächste Woche',
                'issue_date' => $today->copy()->addDays(7)->format('Y-m-d'),
                'return_date' => $today->copy()->addDays(14)->format('Y-m-d'),
            ],
            // 5) Sehr langer Verleih
            [
                'name' => 'Langzeit-Verleih extern',
                'issue_date' => $today->copy()->subDays(1)->format('Y-m-d'),
                'return_date' => $today->copy()->addDays(60)->format('Y-m-d'),
            ],
            // 6) Überlappend mit #2 (gleiche Artikel)
            [
                'name' => 'Paralleler Verleih (Überlappung)',
                'issue_date' => $today->copy()->subDays(1)->format('Y-m-d'),
                'return_date' => $today->copy()->addDays(5)->format('Y-m-d'),
            ],
        ];

        foreach ($externalScenarios as $scenario) {
            $issue = ExternalIssue::create([
                'name' => $scenario['name'],
                'issue_date' => $scenario['issue_date'],
                'return_date' => $scenario['return_date'],
                'issued_by_id' => $userId,
                'received_by_id' => $userId,
                'material_value' => rand(50, 500) + rand(0, 99) / 100,
                'external_name' => 'Testfirma ' . rand(1, 99),
                'external_address' => 'Teststraße ' . rand(1, 50) . ', Hamburg',
                'external_email' => 'test' . rand(1, 99) . '@example.com',
                'external_phone' => '+49 40 ' . rand(1000000, 9999999),
                'return_remarks' => '',
                'special_items_done' => false,
            ]);

            // Verknüpfe 1-3 zufällige Artikel
            $randomArticles = collect($articleIds)->shuffle()->take(rand(1, min(3, count($articleIds))));
            foreach ($randomArticles as $artId) {
                $issue->articles()->attach($artId, ['quantity' => rand(1, 3)]);
            }

            $this->command->info("  External: {$issue->name} ({$scenario['issue_date']} → " . ($scenario['return_date'] ?? 'offen') . ")");
        }

        $this->command->info('InventoryIssueTestSeeder fertig: 7 interne + 6 externe Issues erstellt.');
    }
}
