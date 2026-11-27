<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiExport;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Mockery;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-export-formats: POST /api/v1/ai/exports mit `format` pdf | xlsx | csv (Entgrenzen P2).
 */
final class AiExportFormatsApiTest extends TestCase
{
    use GrantsAiAccess;

    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        config(['ai.enabled' => true]);
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => false,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $client->getKey(),
        ]);

        Storage::fake('local');
    }

    private function actingAsToken(User $user): User
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);

        return $user;
    }

    private function fakePdf(): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')->andReturnSelf();
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('save')->andReturnUsing(function (string $path) use ($pdf) {
            file_put_contents($path, '%PDF-1.4 test');

            return $pdf;
        });
        $this->app->instance(PdfWrapper::class, $pdf);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Budget-Rangliste 2026',
            'subtitle' => 'Abrechnungszeitraum 2026',
            'note' => 'Quelle: artwork',
            'columns' => ['Projekt', 'Einnahmen', 'Datum'],
            'rows' => [['Ten Ways to Fold a Song', '6500.00', '22.03.2026'], ['Möwe', '-322.5', '01.06.2026']],
        ], $overrides);
    }

    private function contents(AiExport $export): string
    {
        return (string) Storage::disk('local')->get($export->storagePath());
    }

    #[Test]
    public function export_xlsx_returns_download_with_spreadsheet_mime(): void
    {
        $this->actingAsToken(User::factory()->create());

        $payload = $this->postJson('/api/v1/ai/exports', $this->payload(['format' => 'xlsx']))
            ->assertCreated()
            ->json();

        $this->assertSame('xlsx', $payload['format']);
        $this->assertSame(self::XLSX, $payload['mime_type']);
        $this->assertSame('Budget-Rangliste 2026.xlsx', $payload['filename']);

        $export = AiExport::query()->sole();
        $sheet = IOFactory::load(Storage::disk('local')->path($export->storagePath()))->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        $this->assertSame('Budget-Rangliste 2026', $rows[0][0]);
        $this->assertSame('Abrechnungszeitraum 2026', $rows[1][0]);
        $header = array_search(['Projekt', 'Einnahmen', 'Datum'], $rows, true);
        $this->assertIsInt($header);
        $this->assertSame('Ten Ways to Fold a Song', $rows[$header + 1][0]);
        // Zahlen als Zahl (Summen in Excel), Datum bleibt Text
        $this->assertEquals(6500, $rows[$header + 1][1]);
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell([2, $header + 2])->getDataType());
        $this->assertEquals(-322.5, $rows[$header + 2][1]);
        $this->assertSame('22.03.2026', $rows[$header + 1][2]);
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell([3, $header + 2])->getDataType());
    }

    #[Test]
    public function export_csv_has_bom_semicolon_and_header(): void
    {
        $this->actingAsToken(User::factory()->create());

        $payload = $this->postJson('/api/v1/ai/exports', $this->payload([
            'format' => 'csv',
            'rows' => [['Faust; Teil 1', '6500.00', 'mit "Zitat"']],
        ]))->assertCreated()->json();

        $this->assertSame('csv', $payload['format']);
        $this->assertSame('text/csv; charset=UTF-8', $payload['mime_type']);
        $this->assertSame('Budget-Rangliste 2026.csv', $payload['filename']);

        $csv = $this->contents(AiExport::query()->sole());
        $this->assertStringStartsWith("\u{FEFF}Projekt;Einnahmen;Datum\r\n", $csv);
        $this->assertStringContainsString("\"Faust; Teil 1\";6500.00;\"mit \"\"Zitat\"\"\"\r\n", $csv);
        $this->assertStringNotContainsString('Abrechnungszeitraum', $csv);
        $this->assertStringNotContainsString('Quelle', $csv);
    }

    #[Test]
    public function export_defaults_to_pdf_without_format(): void
    {
        $this->fakePdf();
        $this->actingAsToken(User::factory()->create());

        $payload = $this->postJson('/api/v1/ai/exports', $this->payload())->assertCreated()->json();

        $this->assertSame('pdf', $payload['format']);
        $this->assertSame('application/pdf', $payload['mime_type']);
        $this->assertSame('Budget-Rangliste 2026.pdf', $payload['filename']);
        $this->assertStringStartsWith('%PDF', $this->contents(AiExport::query()->sole()));
    }

    #[Test]
    public function export_rejects_unknown_format(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->postJson('/api/v1/ai/exports', $this->payload(['format' => 'docx']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
        $this->assertSame(0, AiExport::query()->count());
    }

    #[Test]
    public function export_escapes_formula_like_cells(): void
    {
        $this->actingAsToken(User::factory()->create());
        $rows = [['=HYPERLINK("http://evil","x")', '+1+2', '@SUM(A1)'], ['-5', '-cmd', 'ok']];

        $this->postJson('/api/v1/ai/exports', $this->payload(['format' => 'csv', 'rows' => $rows]))->assertCreated();
        $csv = $this->contents(AiExport::query()->sole());
        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://evil\"\",\"\"x\"\")\";'+1+2;'@SUM(A1)\r\n", $csv);
        // Negative Zahl bleibt Zahl, Minus vor Text nicht (Tab/CR am Anfang entfernt schon TrimStrings)
        $this->assertStringContainsString("-5;'-cmd;ok\r\n", $csv);

        $this->postJson('/api/v1/ai/exports', $this->payload(['format' => 'xlsx', 'rows' => $rows]))->assertCreated();
        $xlsx = AiExport::query()->where('file', 'like', '%.xlsx')->sole();
        $sheet = IOFactory::load(Storage::disk('local')->path($xlsx->storagePath()))->getActiveSheet();
        $formulas = 0;
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $formulas += $cell->getDataType() === DataType::TYPE_FORMULA ? 1 : 0;
            }
        }
        $this->assertSame(0, $formulas);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function formats(): array
    {
        return [
            'pdf' => ['pdf', 'application/pdf'],
            'xlsx' => ['xlsx', self::XLSX],
            'csv' => ['csv', 'text/csv'],
        ];
    }

    #[Test]
    #[DataProvider('formats')]
    public function export_download_respects_owner_and_expiry(string $format, string $mime): void
    {
        $this->fakePdf();
        $owner = $this->actingAsToken(User::factory()->create());
        $url = $this->postJson('/api/v1/ai/exports', $this->payload(['format' => $format]))
            ->assertCreated()
            ->json('url');
        $this->flushHeaders();

        $this->grantAiAccess($owner);
        $response = $this->actingAs($owner)->get($url)->assertOk();
        $this->assertStringContainsString($mime, (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'Budget-Rangliste 2026.' . $format,
            (string) $response->headers->get('Content-Disposition')
        );

        $stranger = $this->grantAiAccess(User::factory()->create());
        $this->actingAs($stranger)->get($url)->assertNotFound();

        AiExport::query()->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($owner)->get($url)->assertNotFound();
    }
}
