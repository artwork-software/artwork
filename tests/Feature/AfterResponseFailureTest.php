<?php

namespace Tests\Feature;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Services\ShiftPlanRequestService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * Code in der Terminate-Phase (dispatch()->afterResponse(), terminate() von Middleware) läuft, nachdem die
 * Antwort bereits gesendet wurde. Eine Exception dort endet auf Prod als "Cannot modify header information -
 * headers already sent"-Fatal, weil Laravel versucht, eine Fehlerseite hinterherzuschicken — die eigentliche
 * Ursache bleibt verdeckt. Fehler müssen dort deshalb gemeldet statt geworfen werden.
 */
final class AfterResponseFailureTest extends FeatureTestCase
{
    public static int $broadcastAttempts = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$broadcastAttempts = 0;

        // Broadcasts wirklich ausführen (FeatureTestCase faked den Bus komplett), aber gegen einen
        // WebSocket-Server, der nicht antwortet.
        Bus::fake()->except([BroadcastEvent::class]);
        Broadcast::extend('failing', static fn (): Broadcaster => new class extends Broadcaster {
            public function auth($request): mixed
            {
                return null;
            }

            public function validAuthenticationResponse($request, $result): mixed
            {
                return null;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                AfterResponseFailureTest::$broadcastAttempts++;

                throw new BroadcastException('Pusher error: cURL error 28: Operation timed out after 5001 milliseconds.');
            }
        });
        config([
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'broadcasting.default' => 'failing',
        ]);
    }

    private function runAfterResponseClosures(): void
    {
        $closures = Bus::dispatchedAfterResponse(CallQueuedClosure::class);
        $this->assertNotEmpty($closures, 'Es wurde kein afterResponse-Closure dispatcht.');

        $closures->each(fn (CallQueuedClosure $job) => $job->handle($this->app));
    }

    #[Test]
    public function day_remark_broadcast_failure_after_response_is_reported_not_thrown(): void
    {
        $exceptions = Exceptions::fake();
        $settings = app(GeneralCalendarSettings::class);
        $settings->day_remarks_enabled = true;
        $settings->save();
        $this->actingAsUserWith([PermissionEnum::DAY_REMARKS_EDIT]);

        $this->putJson(route('day-remarks.upsert', ['date' => '2026-08-03']), ['remark' => 'Live'])
            ->assertSuccessful();

        $this->runAfterResponseClosures();

        $this->assertSame(1, self::$broadcastAttempts);
        $exceptions->assertReported(BroadcastException::class);
    }

    #[Test]
    public function individual_time_broadcast_failure_after_response_is_reported_not_thrown(): void
    {
        $exceptions = Exceptions::fake();
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('add.update.individualTimesAndShiftPlanComment'), [
            'modelType' => 0,
            'modelId' => $user->id,
            'individualTimes' => [
                [
                    'title' => 'Probe',
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                    'start_date' => '2026-01-01',
                    'break_minutes' => 0,
                ],
            ],
        ])->assertOk();

        $this->runAfterResponseClosures();

        $this->assertSame(1, self::$broadcastAttempts);
        $exceptions->assertReported(BroadcastException::class);
    }

    #[Test]
    public function shift_commit_broadcast_failure_after_response_is_reported_not_thrown(): void
    {
        $exceptions = Exceptions::fake();
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $this->createShift($craft);
        $this->createShift($craft, '2026-05-08');

        app(ShiftService::class)->commitShiftsByDate(
            Carbon::parse('2026-05-04'),
            Carbon::parse('2026-05-10'),
            $craft->id,
            19,
            2026
        );

        $this->runAfterResponseClosures();

        // Nach dem ersten Transportfehler keine weiteren Versuche (hängender Server = Timeout je Schicht)
        $this->assertSame(1, self::$broadcastAttempts);
        $exceptions->assertReported(BroadcastException::class);
    }

    #[Test]
    public function shift_plan_request_broadcast_failure_after_response_is_reported_not_thrown(): void
    {
        $exceptions = Exceptions::fake();
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $this->createShift($craft);
        $request = ShiftPlanRequest::query()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $admin->id,
            'requested_at' => now(),
        ]);

        app(ShiftPlanRequestService::class)->attachFreeShiftsToRequest($request, $admin, true);

        $this->runAfterResponseClosures();

        $this->assertSame(1, self::$broadcastAttempts);
        $exceptions->assertReported(BroadcastException::class);
    }

    #[Test]
    public function api_access_log_failure_in_terminate_is_reported_not_thrown(): void
    {
        $exceptions = Exceptions::fake();
        // z. B. Cache-Store nicht erreichbar oder Cache-Verzeichnis nicht beschreibbar
        RateLimiter::shouldReceive('tooManyAttempts')->andThrow(new RuntimeException('Cache nicht beschreibbar'));

        // Sanctum-Route mit ungültigem Bearer-Token → 401; der Header bleibt stehen (Passport leert ihn bei
        // ungültigen Tokens), also greift ApiAccessLog::terminate() auf den RateLimiter zu. Der Test-Kernel
        // ruft terminate() wie FPM nach dem Senden der Antwort auf.
        $this->withToken('kein-gueltiger-token')
            ->postJson(route('room.search'))
            ->assertUnauthorized();

        $this->assertDatabaseCount('api_log', 0);
        $exceptions->assertReported(
            static fn (RuntimeException $exception): bool => $exception->getMessage() === 'Cache nicht beschreibbar'
        );
    }

    private function createShift(Craft $craft, string $date = '2026-05-06'): Shift
    {
        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);
    }
}
