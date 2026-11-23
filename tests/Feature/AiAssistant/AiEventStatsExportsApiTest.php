<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiExport;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-event-stats-exports: GET /api/v1/ai/events/stats, POST /api/v1/ai/exports,
 * GET /ai/exports/{export} und ai:purge-exports (Feedback 17.11.).
 */
final class AiEventStatsExportsApiTest extends TestCase
{
    use GrantsAiAccess;

    private EventType $type;

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

        $this->type = EventType::factory()->create(['name' => 'Probe']);
        Storage::fake('local');
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function events(int $count, ?Project $project, string $day, ?Room $room = null): void
    {
        $room ??= Room::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            Event::factory()->create([
                'room_id' => $room->id,
                'project_id' => $project?->id,
                'event_type_id' => $this->type->id,
                'start_time' => "{$day} 10:00:00",
                'end_time' => "{$day} 12:00:00",
            ]);
        }
    }

    private function fakePdf(int $times = 1): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')->times($times)->with('pdf.ai-table', Mockery::type('array'))->andReturnSelf();
        $pdf->shouldReceive('setPaper')->times($times)->andReturnSelf();
        $pdf->shouldReceive('save')->times($times)->andReturnUsing(function (string $path) use ($pdf) {
            file_put_contents($path, '%PDF-1.4 test');

            return $pdf;
        });
        $this->app->instance(PdfWrapper::class, $pdf);
    }

    /**
     * @return array<string, mixed>
     */
    private function exportPayload(): array
    {
        return [
            'title' => 'Projekte mit mehr als 30 Terminen im 1. Quartal 2026',
            'subtitle' => '01.01.2026 bis 31.03.2026',
            'columns' => ['Projekt', 'Termine'],
            'rows' => [['Tosca', 42], ['Faust', 31]],
            'note' => 'Quelle: artwork, Terminanzahl je Projekt.',
        ];
    }

    // --- Stats -----------------------------------------------------------------------------

    #[Test]
    public function stats_count_events_per_project_in_the_period_only(): void
    {
        $tosca = Project::factory()->create(['name' => 'Tosca']);
        $faust = Project::factory()->create(['name' => 'Faust']);
        $this->events(3, $tosca, '2026-02-10');
        $this->events(1, $faust, '2026-03-01');
        $this->events(5, $faust, '2026-05-01'); // ausserhalb von Q1
        $this->events(2, null, '2026-01-15');

        $this->actingAsToken(User::factory()->create());
        $payload = $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31&group_by=project')
            ->assertOk()->json();

        $this->assertSame('project', $payload['group_by']);
        $this->assertSame(6, $payload['total_events']);
        $this->assertSame(3, $payload['total_groups']);
        $this->assertSame(['id' => $tosca->id, 'name' => 'Tosca', 'count' => 3], $payload['data'][0]);
        $this->assertSame(['id' => null, 'name' => null, 'count' => 2], $payload['data'][1], 'ohne Projekt');
        $this->assertSame(['id' => $faust->id, 'name' => 'Faust', 'count' => 1], $payload['data'][2]);
        $this->assertFalse($payload['truncated']);
    }

    #[Test]
    public function stats_honour_min_and_max_count_and_limit(): void
    {
        $this->events(4, Project::factory()->create(['name' => 'A']), '2026-02-10');
        $this->events(2, Project::factory()->create(['name' => 'B']), '2026-02-10');
        $this->events(3, Project::factory()->create(['name' => 'C']), '2026-02-10');

        $this->actingAsToken(User::factory()->create());
        $payload = $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31&group_by=project&min_count=3')
            ->assertOk()->json();
        $this->assertSame(['A', 'C'], array_column($payload['data'], 'name'));
        $this->assertSame(9, $payload['total_events'], 'vor den Grenzen');

        $limited = $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31&group_by=project&max_count=3&limit=1')
            ->assertOk()->json();
        $this->assertSame(['C'], array_column($limited['data'], 'name'));
        $this->assertSame(2, $limited['total_groups']);
        $this->assertTrue($limited['truncated']);
    }

    #[Test]
    public function stats_group_by_room_and_type(): void
    {
        $room = Room::factory()->create(['name' => 'Studio 1']);
        $this->events(2, Project::factory()->create(), '2026-02-10', $room);

        $this->actingAsToken(User::factory()->create());
        $byRoom = $this->getJson('/api/v1/ai/events/stats?from=2026-02-01&to=2026-02-28&group_by=room')->assertOk()->json();
        $this->assertSame([['id' => $room->id, 'name' => 'Studio 1', 'count' => 2]], $byRoom['data']);

        $byType = $this->getJson('/api/v1/ai/events/stats?from=2026-02-01&to=2026-02-28&group_by=type')->assertOk()->json();
        $this->assertSame([['id' => $this->type->id, 'name' => 'Probe', 'count' => 2]], $byType['data']);
    }

    #[Test]
    public function stats_need_the_read_scope(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31&group_by=project')->assertForbidden();
    }

    #[Test]
    public function stats_validate_period_and_grouping(): void
    {
        $this->actingAsToken(User::factory()->create());
        $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31')->assertStatus(422);
        $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-03-31&group_by=person')->assertStatus(422);
        $this->getJson('/api/v1/ai/events/stats?from=2026-01-01&to=2026-12-31&group_by=project')->assertStatus(422);
    }

    // --- Export ----------------------------------------------------------------------------

    #[Test]
    public function an_export_renders_the_table_and_returns_a_relative_url(): void
    {
        $this->fakePdf();
        $user = User::factory()->create();
        $this->actingAsToken($user);

        $payload = $this->postJson('/api/v1/ai/exports', $this->exportPayload())->assertCreated()->json();

        $export = AiExport::query()->sole();
        $this->assertSame($user->id, $export->user_id);
        $this->assertSame(2, $export->row_count);
        $this->assertSame('/ai/exports/' . $export->id, $payload['url']);
        $this->assertSame('Projekte mit mehr als 30 Terminen im 1. Quartal 2026.pdf', $payload['filename']);
        $this->assertSame(2, $payload['row_count']);
        Storage::disk('local')->assertExists($export->storagePath());
        $this->assertTrue($export->expires_at->between(now()->addDays(6), now()->addDays(8)));
    }

    #[Test]
    public function an_export_rejects_rows_wider_than_the_columns_and_unsupported_cells(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->postJson('/api/v1/ai/exports', array_merge($this->exportPayload(), ['rows' => [['Tosca', 42, 'zu viel']]]))
            ->assertStatus(422)->assertJsonValidationErrors(['rows.0']);
        $this->postJson('/api/v1/ai/exports', array_merge($this->exportPayload(), ['rows' => [['Tosca', ['verschachtelt']]]]))
            ->assertStatus(422);
        $this->postJson('/api/v1/ai/exports', array_merge($this->exportPayload(), ['columns' => []]))
            ->assertStatus(422);
        $this->assertSame(0, AiExport::query()->count());
    }

    #[Test]
    public function an_export_needs_the_read_scope(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/exports', $this->exportPayload())->assertForbidden();
    }

    // --- Download und Purge ----------------------------------------------------------------

    #[Test]
    public function only_the_owner_downloads_an_export_and_only_until_it_expires(): void
    {
        $this->fakePdf();
        $owner = User::factory()->create();
        $this->actingAsToken($owner);
        $url = $this->postJson('/api/v1/ai/exports', $this->exportPayload())->assertCreated()->json('url');
        $this->flushHeaders();

        $this->grantAiAccess($owner);
        $response = $this->actingAs($owner)->get($url)->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));

        $stranger = $this->grantAiAccess(User::factory()->create());
        $this->actingAs($stranger)->get($url)->assertNotFound();

        AiExport::query()->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($owner)->get($url)->assertNotFound();
    }

    #[Test]
    public function purge_deletes_expired_exports_with_their_files(): void
    {
        $this->fakePdf(2);
        $this->actingAsToken(User::factory()->create());
        $this->postJson('/api/v1/ai/exports', $this->exportPayload())->assertCreated();
        $this->postJson('/api/v1/ai/exports', $this->exportPayload())->assertCreated();
        [$expired, $fresh] = AiExport::query()->orderBy('created_at')->get()->all();
        $expired->update(['expires_at' => now()->subDay()]);

        $this->artisan('ai:purge-exports')->assertSuccessful();

        $this->assertSame([$fresh->id], AiExport::query()->pluck('id')->all());
        Storage::disk('local')->assertMissing($expired->storagePath());
        Storage::disk('local')->assertExists($fresh->storagePath());
    }
}
