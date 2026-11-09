<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Artwork\Modules\Event\Models\Event as EventModel;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * Validates every app endpoint against the module's openapi.yaml — the single source of
 * truth the app generates its client from. A failing test here means the spec
 * and the implementation drifted apart; fix whichever is wrong, never both.
 */
final class AppContractTest extends TestCase
{
    use CreatesUserShifts;

    protected function setUp(): void
    {
        parent::setUp();
        Spectator::using('artwork/Modules/AppApi/openapi.yaml');
        Event::fake([UpdateProjectComponentData::class]);
        app(ClientRepository::class)->createPersonalAccessGrantClient('Contract Test Client');
    }

    #[Test]
    public function loginMatchesTheContract(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password')]);

        $this->postJson(route('app.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'Contract Test',
        ])->assertValidRequest()->assertValidResponse(200);

        $this->postJson(route('app.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong',
            'device_name' => 'Contract Test',
        ])->assertValidRequest()->assertValidResponse(401);
    }

    #[Test]
    public function discoverMatchesTheContract(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('app.v1.auth.discover'), [
            'email_hash' => hash('sha256', strtolower($user->email)),
        ])->assertValidRequest()->assertValidResponse(200);

        $this->postJson(route('app.v1.auth.discover'), [
            'email_hash' => 'not-a-hash',
        ])->assertValidResponse(422);
    }

    #[Test]
    public function meAndLogoutMatchTheContract(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.me'))->assertValidRequest()->assertValidResponse(200);
        $this->postJson(route('app.v1.auth.logout'))->assertValidRequest()->assertValidResponse(204);
    }

    #[Test]
    public function dashboardMatchesTheContract(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $this->createShiftForUser($user, now());

        $this->getJson(route('app.v1.dashboard'))->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function shiftPlanMatchesTheContract(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);
        $shift = $this->createShiftForUser($user, now());

        $this->getJson(route('app.v1.shift-plan'))->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.shift-plan', ['start' => now()->toDateString()]))
            ->assertValidResponse(422);
        $this->getJson(route('app.v1.shift-plan.shift', $shift))
            ->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function calendarMatchesTheContract(): void
    {
        $this->actingAsApiUserWith();
        EventType::factory()->create();

        $this->getJson(route('app.v1.calendar'))->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function shiftListMatchesTheContract(): void
    {
        $user = $this->actingAsApiUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $this->createShiftForUser($user, now(), ['is_committed' => true]);

        $this->getJson(route('app.v1.shift-list'))->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function projectEndpointsMatchTheContract(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);

        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Notes',
            'type' => 'TextArea',
            'data' => ['label' => 'Notes'],
        ]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 0]);

        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects'))->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.projects.show', $project))
            ->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.projects.tab', [$project, $tab]))
            ->assertValidRequest()->assertValidResponse(200);
        $this->patchJson(
            route('app.v1.projects.component.update', [$project, $tab, $component]),
            ['data' => ['text' => "Zeile 1\nZeile 2"]],
        )->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function eventEndpointsMatchTheContract(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $project->users()->attach($user->id);
        $eventType = EventType::factory()->create();

        $created = $this->postJson(route('app.v1.projects.events.store', $project), [
            'name' => 'Premiere',
            'start' => now()->setTime(19, 0)->toIso8601String(),
            'end' => now()->setTime(22, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => null,
            'event_type_id' => $eventType->id,
        ])->assertValidRequest()->assertValidResponse(201);

        $eventId = $created->json('event.id');
        $this->getJson(route('app.v1.projects.events.show', [$project, $eventId]))
            ->assertValidRequest()->assertValidResponse(200);
        $this->patchJson(route('app.v1.projects.events.update', [$project, $eventId]), [
            'name' => 'Premiere (verschoben)',
            'start' => now()->setTime(20, 0)->toIso8601String(),
            'end' => now()->setTime(23, 0)->toIso8601String(),
            'all_day' => false,
            'room_id' => null,
            'event_type_id' => $eventType->id,
        ])->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function shiftEndpointsMatchTheContract(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project->users()->attach($user->id);
        $craft = Craft::factory()->create();
        $qualification = ShiftQualification::factory()->create();
        // Schichten ohne Termin brauchen einen Raum (wie im Web)
        $room = Room::factory()->create();

        $created = $this->postJson(route('app.v1.projects.shifts.store', $project), [
            'day' => now()->toDateString(),
            'start' => '18:00',
            'end' => '23:00',
            'break_minutes' => 30,
            'description' => null,
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'qualifications' => [
                ['shift_qualification_id' => $qualification->id, 'value' => 2],
            ],
        ])->assertValidRequest()->assertValidResponse(201);

        $shiftId = $created->json('shift.id');
        $this->getJson(route('app.v1.projects.shifts.show', [$project, $shiftId]))
            ->assertValidRequest()->assertValidResponse(200);
        $this->patchJson(route('app.v1.projects.shifts.update', [$project, $shiftId]), [
            'day' => now()->toDateString(),
            'start' => '19:00',
            'end' => '23:30',
            'break_minutes' => 45,
            'description' => 'Umbau',
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'qualifications' => [
                ['shift_qualification_id' => $qualification->id, 'value' => 2],
            ],
        ])->assertValidRequest()->assertValidResponse(200);

        $this->getJson(route('app.v1.projects.shifts.workers', [$project, $shiftId]))
            ->assertValidRequest()->assertValidResponse(200);

        $colleague = User::factory()->create();
        $this->postJson(route('app.v1.projects.shifts.workers.store', [$project, $shiftId]), [
            'worker_id' => $colleague->id,
            'worker_type' => 'user',
            'shift_qualification_id' => $qualification->id,
        ])->assertValidRequest()->assertValidResponse(201);
        $this->deleteJson(route('app.v1.projects.shifts.workers.destroy', [$project, $shiftId]), [
            'worker_id' => $colleague->id,
            'worker_type' => 'user',
        ])->assertValidRequest()->assertValidResponse(200);

        $this->deleteJson(route('app.v1.projects.shifts.destroy', [$project, $shiftId]))
            ->assertValidResponse(204);
    }

    #[Test]
    public function commentAndTaskEndpointsMatchTheContract(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $checklist = Checklist::factory()->create(['project_id' => $project->id, 'private' => false]);

        Passport::actingAs($user, ['app']);

        $this->postJson(route('app.v1.projects.comments.store', $project), [
            'text' => "Erste Zeile\nZweite Zeile",
            'tab_id' => null,
        ])->assertValidRequest()->assertValidResponse(201);

        $created = $this->postJson(route('app.v1.projects.tasks.store', [$project, $checklist]), [
            'name' => 'Materialliste prüfen',
        ])->assertValidRequest()->assertValidResponse(201);

        $this->patchJson(route('app.v1.projects.tasks.toggle-done', [$project, $created->json('task.id')]))
            ->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function teamEndpointsMatchTheContract(): void
    {
        $project = Project::factory()->create();
        $user = $this->actingAsApiUserWith(PermissionEnum::PROJECT_MANAGEMENT->value);
        $project->users()->attach($user->id);
        $candidate = User::factory()->create();

        $this->getJson(route('app.v1.projects.team.candidates', [$project, 'q' => $candidate->last_name]))
            ->assertValidRequest()->assertValidResponse(200);

        $this->postJson(route('app.v1.projects.team.store', $project), [
            'user_id' => $candidate->id,
            'can_write' => true,
        ])->assertValidRequest()->assertValidResponse(201);

        $this->patchJson(route('app.v1.projects.team.update', [$project, $candidate]), [
            'is_manager' => true,
        ])->assertValidRequest()->assertValidResponse(200);

        $this->deleteJson(route('app.v1.projects.team.destroy', [$project, $candidate]))
            ->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function ticketingEndpointsMatchTheContract(): void
    {
        $dateId = '7a2e3d4c-5b6f-4a71-9b82-0c3d4e5f6a71';
        $ticketId = '1b2c3d4e-5f60-4a71-8b92-0c1d2e3f4a5b';
        $passId = '2c3d4e5f-6a71-4b82-9c03-1d2e3f4a5b6c';
        $categoryId = '3d4e5f6a-7b82-4c93-8d14-2e3f4a5b6c7d';
        $seat = ['section' => null, 'row' => null, 'number' => null];
        $api = 'https://tickets.test/api/integration/v1';
        config()->set('services.tickets.url', 'https://tickets.test');
        TicketingConnection::query()->create([
            'tickets_url' => 'https://tickets.test', 'organization_id' => 'org_1', 'organization_slug' => 'theater',
            'dashboard_url' => 'https://tickets.test/dashboard', 'api_key' => 'tk_plain', 'oauth_client_id' => 1,
            'connected_by_user_id' => null,
        ]);
        $event = EventModel::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'start_time' => now()->addDay()->setTime(19, 30),
            'end_time' => now()->addDay()->setTime(22, 0),
        ]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => $dateId, 'classes' => [],
        ]);
        Passport::actingAs($this->adminUser(), ['app']);
        Http::fake([
            $api . '/dates?ids=*' => Http::response([
                ['id' => $dateId, 'status' => 'scheduled', 'capacity' => 300, 'sold' => 1, 'checkedInCount' => 1],
            ]),
            $api . "/dates/{$dateId}/check-ins/revert" => Http::response(['reverted' => true]),
            $api . "/dates/{$dateId}/check-ins" => Http::response([
                'scanId' => $ticketId, 'outcome' => 'accepted',
                'ticket' => [
                    'id' => $ticketId, 'code' => 'ABCD1234', 'holderName' => 'Ada Lovelace', 'categoryName' => 'Parkett',
                    'requiresProof' => false, 'seat' => $seat, 'checkedInAt' => '2027-02-01T19:12:00.000Z',
                ],
            ]),
            $api . "/dates/{$dateId}/passes*" => Http::response([
                'seated' => false,
                'categories' => [['id' => $categoryId, 'name' => 'Parkett', 'priceCents' => 2900, 'available' => 40]],
                'passes' => [[
                    'id' => $passId, 'code' => 'P-1234', 'name' => '10er-Karte', 'holderName' => 'Ada Lovelace',
                    'holderEmail' => 'ada@example.org', 'subscription' => true, 'usesLeft' => 4, 'validUntil' => null,
                    'block' => 'notAtDoor', 'toPay' => [['priceCategoryId' => $categoryId, 'cents' => 900]],
                ]],
            ]),
            $api . "/dates/{$dateId}/pass-admissions" => Http::response([
                'orderNumber' => 'ATK-2027-000001', 'ticketCode' => 'ABCD1234', 'paidCents' => 900, 'checkedInAt' => null,
            ]),
            $api . "/dates/{$dateId}" => Http::response([
                'id' => $dateId, 'status' => 'scheduled', 'doorsAt' => null, 'capacity' => 300, 'sold' => 1,
                'ticketCount' => 1, 'checkedInCount' => 1, 'revenueCents' => 2900,
                'tickets' => [[
                    'id' => $ticketId, 'code' => 'ABCD1234', 'status' => 'valid', 'holderName' => null, 'email' => null,
                    'categoryName' => 'Parkett', 'reductionName' => null, 'priceCents' => 2900, 'requiresProof' => false,
                    'seat' => $seat, 'checkedInAt' => '2027-02-01T19:12:00.000Z',
                ]],
            ]),
        ]);

        $this->getJson(route('app.v1.me'))->assertValidResponse(200);
        $this->getJson(route('app.v1.ticketing.dates', [
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(6)->format('Y-m-d'),
        ]))->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.ticketing.dates.show', $event))->assertValidRequest()->assertValidResponse(200);
        $this->postJson(route('app.v1.ticketing.check-ins', $event), [
            'scan_id' => $ticketId, 'payload' => "atk:{$ticketId}:secret",
        ])->assertValidRequest()->assertValidResponse(200);
        $this->postJson(route('app.v1.ticketing.check-ins.revert', $event), ['ticket_id' => $ticketId])
            ->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.ticketing.passes', [$event, 'q' => 'Ada']))
            ->assertValidRequest()->assertValidResponse(200);
        $this->postJson(route('app.v1.ticketing.pass-admissions', $event), [
            'admission_id' => '4e5f6a7b-8c93-4da4-9e25-3f4a5b6c7d8e',
            'pass_id' => $passId,
            'price_category_id' => $categoryId,
        ])->assertValidRequest()->assertValidResponse(200);
    }

    #[Test]
    public function inventoryEndpointsMatchTheContract(): void
    {
        $ready = new InventoryArticleStatus(['name' => 'Einsatzbereit', 'color' => '#00aa00', 'order' => 1]);
        $ready->default = true;
        $ready->save();
        $category = InventoryCategory::factory()->create();
        InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        $plain = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => null,
            'description' => null,
        ]);
        $detailed = InventoryArticle::factory()->create(['quantity' => 1, 'is_detailed_quantity' => true]);
        $detailed->properties()->attach(
            InventoryArticleProperties::factory()->create(['type' => 'room'])->id,
            ['value' => (string) Room::factory()->create()->id],
        );
        InventoryArticleImage::factory()->create([
            'inventory_article_id' => $detailed->id,
            'image' => 'inventory_articles/a.jpg',
            'thumbnail' => null,
        ]);
        InventoryDetailedQuantityArticle::factory()->create([
            'inventory_article_id' => $detailed->id,
            'inventory_article_status_id' => $ready->id,
            'detail_number' => 1,
            'quantity' => 1,
        ]);
        $this->actingAsApiUserWith(PermissionEnum::INVENTORY_VIEW->value);

        $this->getJson(route('app.v1.inventory.categories'))->assertValidRequest()->assertValidResponse(200);
        $this->getJson(route('app.v1.inventory.articles', ['category_id' => $category->id, 'page' => 1]))
            ->assertValidRequest()
            ->assertValidResponse(200);
        $this->getJson(route('app.v1.inventory.articles'))->assertValidRequest()->assertValidResponse(200);
        foreach ([$plain, $detailed] as $article) {
            $this->getJson(route('app.v1.inventory.articles.show', $article))
                ->assertValidRequest()
                ->assertValidResponse(200);
        }
        $this->getJson(route('app.v1.inventory.articles.availability', [
            'article' => $plain, 'start' => '2026-11-10', 'end' => '2026-11-12',
        ]))->assertValidRequest()->assertValidResponse(200);
    }
}
