<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Exports\ExternalIssueListExport;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Exports\InternalIssueListExport;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Materialausgabe-Bücher: Status-Filter, Sortierung, Excel-Export und Sammelaktionen.
 */
final class MaterialIssueListFeaturesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->actingAsUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);
    }

    private function internalIssue(string $name, string $start = '2026-08-01', array $attributes = []): InternalIssue
    {
        return InternalIssue::create(array_merge([
            'name' => $name,
            'start_date' => $start,
            'start_time' => '10:00',
            'end_date' => $start,
            'end_time' => '18:00',
            'special_items_done' => false,
        ], $attributes));
    }

    private function externalIssue(string $name, array $attributes = []): ExternalIssue
    {
        return ExternalIssue::create(array_merge([
            'name' => $name,
            'material_value' => 100,
            'issue_date' => now()->subDays(10)->toDateString(),
            'return_date' => now()->addDays(10)->toDateString(),
            'external_name' => 'Firma ' . $name,
            'issued_by_id' => $this->user->id,
            'special_items_done' => false,
        ], $attributes));
    }

    /**
     * @return array<int, string>
     */
    private function listedNames(string $routeName, array $query = []): array
    {
        $issues = $this->get(route($routeName, $query))->assertOk()->viewData('page')['props']['issues'];

        return array_column($issues['data'], 'name');
    }

    /**
     * Nach dem Löschen der letzten Einträge einer Seite zeigt die Adresse hinter das Ende:
     * die Liste fällt auf die letzte vorhandene Seite zurück statt leer zu bleiben.
     */
    #[Test]
    public function lists_fall_back_to_the_last_page_when_the_requested_page_is_beyond_the_end(): void
    {
        $this->internalIssue('Intern A');
        $this->internalIssue('Intern B');
        $this->externalIssue('Extern A');
        $this->externalIssue('Extern B');

        foreach (['issue-of-material.index', 'extern-issue-of-material.index'] as $routeName) {
            $issues = $this->get(route($routeName, ['entitiesPerPage' => 1, 'page' => 9]))
                ->assertOk()
                ->viewData('page')['props']['issues'];

            $this->assertCount(1, $issues['data'], $routeName);
            $this->assertSame(2, $issues['current_page'], $routeName);
        }
    }

    #[Test]
    public function internal_list_sorts_by_allowed_columns_and_ignores_unknown_ones(): void
    {
        $this->internalIssue('Bravo', '2026-08-01');
        $this->internalIssue('Alpha', '2026-08-03');
        $this->internalIssue('Charlie', '2026-08-02');

        $this->assertSame(['Bravo', 'Charlie', 'Alpha'], $this->listedNames('issue-of-material.index'));
        $this->assertSame(
            ['Alpha', 'Bravo', 'Charlie'],
            $this->listedNames('issue-of-material.index', ['sort' => 'name'])
        );
        $this->assertSame(
            ['Charlie', 'Bravo', 'Alpha'],
            $this->listedNames('issue-of-material.index', ['sort' => 'name', 'direction' => 'desc'])
        );
        // unbekannte Spalte: Standardreihenfolge statt SQL-Fehler oder freier Spaltenwahl
        $this->assertSame(
            ['Bravo', 'Charlie', 'Alpha'],
            $this->listedNames('issue-of-material.index', ['sort' => 'notes, id desc'])
        );
    }

    #[Test]
    public function internal_list_filters_issues_with_open_special_items(): void
    {
        $open = $this->internalIssue('Offen');
        $open->specialItems()->create(['name' => 'Nebelmaschine', 'quantity' => 1]);
        $done = $this->internalIssue('Erledigt', '2026-08-02', ['special_items_done' => true]);
        $done->specialItems()->create(['name' => 'Konfetti', 'quantity' => 2]);
        $this->internalIssue('Ohne Sonderartikel', '2026-08-03');

        $this->assertSame(['Offen'], $this->listedNames('issue-of-material.index', ['special_open' => 1]));
        $this->assertCount(3, $this->listedNames('issue-of-material.index'));
    }

    #[Test]
    public function internal_export_uses_the_list_filters_and_selection(): void
    {
        $first = $this->internalIssue('Bühnenbau');
        $this->internalIssue('Lichtprobe', '2026-08-02');

        $response = $this->get(route('issue-of-material.export', ['q' => 'Bühnen']))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $export = new InternalIssueListExport(
            InternalIssue::with(['room', 'project', 'responsibleUsers', 'articles', 'specialItems', 'files'])
                ->whereKey($first->id)->get()
        );
        $rows = $export->array();
        $this->assertCount(count($export->headings()), $rows[0]);
        $this->assertSame('Bühnenbau', $rows[0][0]);
        $this->assertSame('01.08.2026 10:00', $rows[0][1]);
        $this->assertSame('01.08.2026 18:00', $rows[0][2]);
    }

    #[Test]
    public function internal_bulk_delete_removes_the_selection_and_validates_the_ids(): void
    {
        $first = $this->internalIssue('Erste');
        $second = $this->internalIssue('Zweite', '2026-08-02');
        $kept = $this->internalIssue('Bleibt', '2026-08-03');

        $this->postJson(route('issue-of-material.bulk-delete'), ['ids' => [$first->id, 999999]])->assertNotFound();
        $this->postJson(route('issue-of-material.bulk-delete'), ['ids' => []])->assertUnprocessable();
        $this->assertNotNull(InternalIssue::find($first->id));

        $this->postJson(route('issue-of-material.bulk-delete'), ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->assertExactJson(['deleted' => 2]);

        $this->assertNull(InternalIssue::find($first->id));
        $this->assertNull(InternalIssue::find($second->id));
        $this->assertNotNull(InternalIssue::find($kept->id));
    }

    #[Test]
    public function list_actions_require_the_disposition_permission(): void
    {
        $internal = $this->internalIssue('Geschützt');
        $external = $this->externalIssue('Geschützt');
        $this->actingAs(User::factory()->create());

        $this->get(route('issue-of-material.export'))->assertForbidden();
        $this->get(route('extern-issue-of-material.export'))->assertForbidden();
        $this->postJson(route('issue-of-material.bulk-delete'), ['ids' => [$internal->id]])->assertForbidden();
        $this->postJson(route('extern-issue-of-material.bulk-delete'), ['ids' => [$external->id]])->assertForbidden();
        $this->get(route('issue-of-material.print-many', ['ids' => $internal->id]))->assertForbidden();
        $this->get(route('extern-issue-of-material.print-many', ['ids' => $external->id]))->assertForbidden();

        $this->assertNotNull(InternalIssue::find($internal->id));
        $this->assertNotNull(ExternalIssue::find($external->id));
    }

    #[Test]
    public function print_many_combines_the_selected_issues_without_attaching_files(): void
    {
        $first = $this->internalIssue('Erste');
        $second = $this->internalIssue('Zweite', '2026-08-02');

        $response = $this->get(route('issue-of-material.print-many', ['ids' => $first->id . ',' . $second->id]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame(0, $first->files()->count());
        // je Ausgabe mindestens eine eigene Seite
        $this->assertGreaterThanOrEqual(2, preg_match_all('~/Type\s*/Page\b(?!s)~', (string) $response->getContent()));

        $this->get(route('issue-of-material.print-many'))->assertStatus(422);
        $this->get(route('issue-of-material.print-many', ['ids' => '999999']))->assertNotFound();
    }

    #[Test]
    public function external_list_filters_by_return_state(): void
    {
        $this->externalIssue('Läuft');
        $this->externalIssue('Überfällig', ['return_date' => now()->subDay()->toDateString()]);
        $this->externalIssue('Zurück', [
            'return_date' => now()->subDay()->toDateString(),
            'return_status' => ExternalIssue::RETURN_STATUS_RETURNED,
            'received_by_id' => $this->user->id,
        ]);

        $route = 'extern-issue-of-material.index';
        $this->assertSame(['Läuft'], $this->listedNames($route, ['return_state' => 'open']));
        $this->assertSame(['Überfällig'], $this->listedNames($route, ['return_state' => 'overdue']));
        $this->assertSame(['Zurück'], $this->listedNames($route, ['return_state' => 'returned']));
        // der bisherige Schalter "Nur überfällige" bleibt gültig
        $this->assertSame(['Überfällig'], $this->listedNames($route, ['overdue_only' => 1]));
        $this->assertCount(3, $this->listedNames($route, ['return_state' => 'irgendwas']));
    }

    #[Test]
    public function external_list_sorts_and_filters_open_special_items(): void
    {
        $cheap = $this->externalIssue('Günstig', ['material_value' => 10]);
        $this->externalIssue('Teuer', ['material_value' => 900]);
        $this->externalIssue('Mittel', ['material_value' => 300]);
        $cheap->specialItems()->create(['name' => 'Kabeltrommel', 'quantity' => 1]);

        $route = 'extern-issue-of-material.index';
        $this->assertSame(
            ['Teuer', 'Mittel', 'Günstig'],
            $this->listedNames($route, ['sort' => 'material_value', 'direction' => 'desc'])
        );
        $this->assertSame(['Günstig'], $this->listedNames($route, ['special_open' => 1]));
    }

    #[Test]
    public function external_export_and_bulk_delete_work_on_the_selection(): void
    {
        $returned = $this->externalIssue('Zurück', [
            'return_status' => ExternalIssue::RETURN_STATUS_RETURNED,
            'received_by_id' => $this->user->id,
        ]);
        $overdue = $this->externalIssue('Überfällig', ['return_date' => now()->subDay()->toDateString()]);
        $running = $this->externalIssue('Läuft');

        $response = $this->get(route('extern-issue-of-material.export', ['ids' => $returned->id]))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $export = new ExternalIssueListExport(
            ExternalIssue::with(['issuedBy', 'receivedBy', 'project', 'articles', 'specialItems', 'files'])
                ->orderBy('id')->get()
        );
        $rows = $export->array();
        $this->assertCount(count($export->headings()), $rows[0]);
        $this->assertSame([__('Returned'), __('Return open – material blocked'), __('Issued')], array_column($rows, 5));

        $this->postJson(route('extern-issue-of-material.bulk-delete'), ['ids' => [$returned->id, $overdue->id]])
            ->assertOk()
            ->assertExactJson(['deleted' => 2]);
        $this->assertNull(ExternalIssue::find($returned->id));
        $this->assertNotNull(ExternalIssue::find($running->id));
    }
}
