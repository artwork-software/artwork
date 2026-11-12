<?php

namespace Tests\Browser;

use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Services\AiActionService;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\DuskTestCase;

/**
 * core-ki-bar-confirm: Bestaetigen, Abbrechen, Rueckgaengig in der Bar (Konzept W2 §7, W3 §3).
 *
 * Gegen den Entwicklungs-Stub — der steht hier fuer artwork-ai und fuehrt die bestaetigte Aktion
 * ueber die echten Endpunkte aus. Die Aktion selbst legt der Test in artwork an; die UUID steht in
 * der Nachricht, damit das Stub-Skript sie im `confirm.request` nennt.
 */
final class AiActionConfirmTest extends DuskTestCase
{
    private const MONDAY = '2026-09-14';

    private ?User $user = null;

    private ?Project $project = null;

    private ?Room $room = null;

    private ?EventType $eventType = null;

    /** @var array{enabled: bool, b: bool, c: bool}|null */
    private ?array $previousSettings = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipWithoutStub();

        $settings = app(AiAssistantSettings::class);
        $this->previousSettings = [
            'enabled' => $settings->enabled,
            'b' => $settings->level_b_enabled,
            'c' => $settings->level_c_enabled,
        ];

        // Stufe C setzt Stufe B voraus (core-modul-settings-policy).
        $settings->enabled = true;
        $settings->level_b_enabled = true;
        $settings->level_c_enabled = true;
        $settings->save();

        $this->artisan('ai:install')->assertSuccessful();

        $this->user = User::factory()->withAiAccess()->create(['language' => 'de']);
        UserCalendarSettings::factory()->create(['user_id' => $this->user->id]);

        // Der Rechte-Cache von spatie ueberdauert Testlaeufe und kann auf Zeilen zeigen, die es in
        // artwork_test nicht (mehr) gibt — deshalb erst leeren und dann ueber die Datenbank suchen.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([PermissionEnum::PROJECT_VIEW->value, PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value] as $name) {
            $this->user->givePermissionTo(
                Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'])
            );
        }

        $this->project = Project::factory()->create(['name' => 'Dusk-Projekt']);
        $this->room = Room::factory()->create(['name' => 'Dusk-Studio']);
        $this->eventType = EventType::factory()->create();
    }

    protected function tearDown(): void
    {
        // Die Raum-Factory legt auch einen Bereich an; ohne dessen Loeschung bleibt er im
        // gemeinsamen artwork_test stehen und laesst fremde Tests ueber Gesamtzahlen scheitern.
        $areaId = $this->room?->area_id;

        Event::withTrashed()->where('project_id', $this->project?->id)->forceDelete();
        AiActionBatch::query()->where('user_id', $this->user?->id)->delete();
        AiConversation::query()->where('user_id', $this->user?->id)->delete();
        $this->project?->forceDelete();
        $this->room?->forceDelete();

        if ($areaId !== null) {
            Area::withTrashed()->whereKey($areaId)->each(static fn (Area $area) => $area->forceDelete());
        }

        $this->eventType?->forceDelete();
        $this->user?->forceDelete();

        if ($this->previousSettings !== null) {
            $settings = app(AiAssistantSettings::class);
            $settings->enabled = $this->previousSettings['enabled'];
            $settings->level_b_enabled = $this->previousSettings['b'];
            $settings->level_c_enabled = $this->previousSettings['c'];
            $settings->save();
        }

        parent::tearDown();
    }

    /** Eine offene Aktion, wie artwork-ai sie schreiben wuerde: zwei Termine anlegen. */
    private function pendingAction(): AiConversationTurn
    {
        $args = [
            'project_id' => $this->project->id,
            'series_spec' => [
                'from' => self::MONDAY,
                'to' => '2026-09-21',
                'weekdays' => [1],
                'start_time' => '19:00',
                'duration_minutes' => 120,
                'room_id' => $this->room->id,
                'type_id' => $this->eventType->id,
                'name' => 'Dusk-Vorstellung',
            ],
        ];

        $conversation = AiConversation::query()->create([
            'user_id' => $this->user->id,
            'title' => 'Dusk',
            'status' => AiConversation::STATUS_AWAITING_CONFIRMATION,
        ]);

        return $conversation->turns()->create([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'tool_calls' => [],
            'context' => [],
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => [
                    'summary' => 'Zwei Vorstellungen anlegen.',
                    'items' => [
                        ['action' => 'create', 'label' => 'Vorstellung am 14.09.'],
                        ['action' => 'create', 'label' => 'Vorstellung am 21.09.'],
                    ],
                ],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    /** 1. Bestaetigen fuehrt aus, danach laesst sich die Aktion zuruecknehmen. */
    #[Test]
    public function confirming_executes_the_action_and_offers_undo(): void
    {
        $turn = $this->pendingAction();

        $this->browse(function (Browser $browser) use ($turn): void {
            $this->ask($browser, 'confirm ' . $turn->id)
                ->waitFor('[data-ai-confirm]')
                // Die Vorschau in der Bar kommt aus dem `confirm.request` des Stubs; die Aktion in
                // artwork liefert Werkzeug und Argumente fuer die Ausfuehrung.
                ->assertSee('Zwei Termine anlegen, einen verschieben.')
                ->click('[data-ai-confirm-accept]')
                ->waitFor('[data-ai-result]', 20);

            $this->assertSame(
                AiActionService::STATUS_EXECUTED,
                $turn->fresh()->status,
                'Die Aktion muss nach dem Bestaetigen ausgefuehrt sein.'
            );
            $this->assertSame(2, Event::query()->where('project_id', $this->project->id)->count());

            // Write-Token nirgends gespeichert (Brief: nie in localStorage/Cookie, nicht im State).
            // Geprueft wird auf JWTs — die Anwendung legt sonst durchaus Dinge im Speicher ab.
            // Gesucht wird die Form eines JWT (drei Teile, mit Punkten) — die verschluesselten
            // Laravel-Cookies (XSRF-TOKEN) beginnen ebenfalls mit „eyJ", haben aber keine Punkte.
            $tokensFound = $browser->script(
                'const values = [...Object.values(localStorage), ...Object.values(sessionStorage),'
                . ' ...document.cookie.split(";").map((part) => part.split("=").slice(1).join("="))];'
                . ' return values.filter((value) => /^eyJ[\\w-]+\\.[\\w-]+\\.[\\w-]+$/.test('
                . 'String(value).trim())).length'
            )[0];
            $this->assertSame(0, (int) $tokensFound, 'Es darf kein Token im Browser-Speicher liegen.');

            $browser->click('[data-ai-undo]')
                ->waitFor('[data-ai-undo-confirm]')
                ->click('[data-ai-undo-confirm]')
                ->waitFor('[data-ai-result-reverted]', 20);

            $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
            $this->assertSame(
                AiActionBatch::STATUS_REVERTED,
                AiActionBatch::query()->where('user_id', $this->user->id)->latest('created_at')->first()?->status
            );
        });
    }

    /** 2. Abbrechen fordert kein Write-Token an und aendert nichts. */
    #[Test]
    public function cancelling_asks_for_no_token_and_changes_nothing(): void
    {
        $turn = $this->pendingAction();

        $this->browse(function (Browser $browser) use ($turn): void {
            $this->ask($browser, 'confirm ' . $turn->id)
                ->waitFor('[data-ai-confirm]')
                ->click('[data-ai-confirm-cancel]')
                ->waitFor('[data-ai-confirm-cancelled]', 15);

            $turn = $turn->fresh();

            $this->assertSame(AiConversationTurn::STATUS_CANCELLED, $turn->status);
            // Ohne ausgestelltes Write-Token gibt es keine jti an der Aktion (W2 §2).
            $this->assertArrayNotHasKey('jti', $turn->pending_action);
            $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
            $this->assertSame(0, AiActionBatch::query()->where('user_id', $this->user->id)->count());
        });
    }

    private function ask(Browser $browser, string $message): Browser
    {
        return $browser->loginAs($this->user)
            ->visit('/dashboard')
            ->waitFor('[data-ai-open]')
            ->click('[data-ai-open]')
            ->waitFor('[data-ai-bar]')
            ->type('[data-ai-input]', $message)
            ->click('[data-ai-send]');
    }

    private function skipWithoutStub(): void
    {
        $probe = (string) Str::uuid();

        try {
            $response = Http::timeout(5)->get($this->baseUrl() . '/ai/dev-stub/calls/' . $probe);
        } catch (ConnectionException $exception) {
            $this->markTestSkipped(
                'Anwendung unter Test nicht erreichbar (' . $this->baseUrl() . '): ' . $exception->getMessage()
            );
        }

        if (!$response->successful()) {
            $this->markTestSkipped(
                'Kein Entwicklungs-Stub unter ' . $this->baseUrl() . ' (HTTP ' . $response->status() . '). '
                . 'Die Stub-Routen gibt es nur in der Umgebung local.'
            );
        }
    }
}
