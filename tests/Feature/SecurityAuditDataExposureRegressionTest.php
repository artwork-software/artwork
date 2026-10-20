<?php

namespace Tests\Feature;

use App\Events\ChatCreated;
use App\Events\NewChatMessage;
use App\Exceptions\Handler;
use App\Exports\SageBookingLogExport;
use Artwork\Core\Api\Middleware\RejectTransientTokens;
use Artwork\Core\Sentry\SentryEventScrubber;
use Artwork\Core\Validation\Rules\HexColorRule;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageBookingLog;
use Artwork\Modules\Budget\Models\SageBookingLogEntry;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShowResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Artwork\Modules\User\Services\UserCalendarAboService;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TransientToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 6: Datenabfluss (M11–M18, N19–N24) und die übrigen Härtungen
 * (M7-Rest, M8, M10, N6, N8, N13, N14, N16, N17, N25–N28).
 */
final class SecurityAuditDataExposureRegressionTest extends FeatureTestCase
{
    #[Test]
    public function transient_cookie_tokens_are_rejected_on_machine_and_app_apis(): void
    {
        $user = User::factory()->create();
        Auth::guard('api')->setUser($user->withAccessToken(new TransientToken()));

        $request = Request::create('/api/v1/rooms');
        $request->setUserResolver(fn (?string $guard = null) => Auth::guard($guard)->user());

        $response = (new RejectTransientTokens())->handle($request, fn () => response('ok'));

        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function the_api_management_cannot_revoke_app_device_tokens(): void
    {
        if (!is_readable(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
        $this->actingAsAdmin();
        $device = User::factory()->create()->createToken('iPhone', ['app'])->token;

        $this->delete(route('api-management.destroy', $device->id))->assertSessionHas('error');

        $this->assertFalse((bool) $device->fresh()->revoked);
    }

    #[Test]
    public function raw_user_models_hide_contract_time_account_and_idp_fields(): void
    {
        $user = User::factory()->create([
            'work_time_balance' => 120,
            'ad_identifier' => 'guid-1',
        ]);

        $array = $user->toArray();

        foreach (['work_time_balance', 'employStart', 'employEnd', 'temporary', 'auth_provider_id',
            'ad_identifier', 'is_developer', 'formated_work_time_balance'] as $field) {
            $this->assertArrayNotHasKey($field, $array, $field);
        }
        $this->assertSame(120, (int) $user->work_time_balance);
    }

    #[Test]
    public function the_money_source_search_returns_only_id_name_and_group_flag(): void
    {
        config(['scout.driver' => 'collection']);
        $this->actingAs(User::factory()->create());
        MoneySource::factory()->create(['name' => 'Kulturstiftung', 'amount' => 50000, 'is_group' => false]);

        $results = $this->getJson(route('money_sources.search', ['query' => 'Kulturstiftung', 'type' => 'single']))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame(['id', 'name', 'is_group'], array_keys($result));
        }
    }

    #[Test]
    public function the_budget_tab_ignores_cells_and_sums_of_other_projects(): void
    {
        $this->actingAsAdmin();
        $budgetService = app(BudgetService::class);
        $own = Project::factory()->create();
        $foreign = Project::factory()->create();
        $budgetService->generateBasicBudgetValues($own);
        $budgetService->generateBasicBudgetValues($foreign);

        $cellOf = static fn (Project $project): ColumnCell => ColumnCell::query()
            ->whereIn('column_id', Column::query()->select('id')->where(
                'table_id',
                Table::query()->where('project_id', $project->id)->value('id')
            ))
            ->firstOrFail();
        $foreignCell = $cellOf($foreign);
        $foreignColumnId = $foreignCell->column_id;
        $sumDetailsBefore = BudgetSumDetails::query()->count();

        request()->merge([
            'selectedCell' => $foreignCell->id,
            'selectedBudgetType' => 'COST',
            'selectedColumn' => $foreignColumnId,
        ]);
        $payload = $budgetService->getBudgetForProjectTab($own, []);

        $this->assertNull($payload['BudgetTab']['budget']['selectedCell']);
        $this->assertNull($payload['BudgetTab']['budget']['selectedSumDetail']);
        $this->assertSame($sumDetailsBefore, BudgetSumDetails::query()->count());

        request()->merge(['selectedCell' => $cellOf($own)->id, 'selectedColumn' => null]);
        $payload = $budgetService->getBudgetForProjectTab($own, []);
        $this->assertNotNull($payload['BudgetTab']['budget']['selectedCell']);
    }

    #[Test]
    public function budget_history_entries_are_removed_for_viewers_without_budget_access(): void
    {
        $rows = [
            ['changes' => [['type' => 'budget', 'translationKey' => 'x']]],
            ['changes' => [['type' => 'project', 'translationKey' => 'y']]],
        ];

        $filtered = app(ChangeService::class)->withoutBudgetEntries($rows);

        $this->assertCount(1, $filtered);
        $this->assertSame('project', $filtered[0]['changes'][0]['type']);
    }

    #[Test]
    public function freelancer_fee_and_note_need_the_terms_right(): void
    {
        $freelancer = Freelancer::factory()->create([
            'note' => 'nur intern',
            'salary_per_hour' => 4000,
            'salary_description' => 'Tagesgage',
        ]);

        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $limited = (new FreelancerShowResource($freelancer))->resolve();
        $this->assertNull($limited['note']);
        $this->assertNull($limited['salary_per_hour']);
        $this->assertNull($limited['salary_description']);

        $this->actingAsAdmin();
        $full = (new FreelancerShowResource($freelancer))->resolve();
        $this->assertSame('nur intern', $full['note']);
        $this->assertSame(4000, (int) $full['salary_per_hour']);
    }

    #[Test]
    public function weekly_hours_of_other_people_need_the_staff_management_right(): void
    {
        $person = User::factory()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer);
        $request = request()->setUserResolver(fn () => $viewer);

        $this->assertNull((new UserShowResource($person))->toArray($request)['weekly_working_hours']);
        $this->assertArrayHasKey('weekly_working_hours', (new UserShowResource($viewer))->toArray($request));
    }

    #[Test]
    public function the_calendar_guest_list_needs_the_ticketing_component(): void
    {
        $project = Project::factory()->create();
        $event = Event::factory()->create(['project_id' => $project->id]);
        $this->mock(TicketingReleaseService::class, function ($mock): void {
            $mock->shouldReceive('sales')->andReturn([
                'released' => true,
                'sold' => 2,
                'capacity' => 10,
                'checkedInCount' => 1,
                'revenueCents' => 4000,
                'tickets' => [['code' => 'A1', 'name' => 'Gast', 'email' => 'gast@example.com']],
            ]);
        });

        $member = User::factory()->create();
        $project->users()->attach($member->id);
        $this->actingAs($member);

        $this->getJson(route('ticketing.sales', $event))
            ->assertOk()
            ->assertJsonMissingPath('tickets')
            ->assertJsonMissingPath('revenueCents')
            ->assertJsonPath('guests_hidden', true)
            ->assertJsonPath('sold', 2);

        $this->actingAsAdmin();
        $this->getJson(route('ticketing.sales', $event))
            ->assertOk()
            ->assertJsonPath('tickets.0.code', 'A1')
            ->assertJsonMissingPath('guests_hidden');
    }

    #[Test]
    public function chat_broadcasts_carry_only_a_fixed_participant_payload(): void
    {
        $sender = User::factory()->create(['email' => 'privat@example.com', 'phone_number' => '0170 1']);
        $chat = Chat::factory()->create();
        $chat->users()->attach($sender->id);
        $message = ChatMessage::factory()->create(['chat_id' => $chat->id, 'sender_id' => $sender->id]);

        $expectedKeys = ['id', 'first_name', 'last_name', 'full_name', 'profile_photo_url'];
        $created = (new ChatCreated($chat))->broadcastWith();
        $this->assertSame($expectedKeys, array_keys($created['chat']['users'][0]));

        $sent = (new NewChatMessage($message))->broadcastWith();
        $this->assertSame($expectedKeys, array_keys($sent['message']['sender']));
        $this->assertStringNotContainsString('privat@example.com', json_encode([$created, $sent]));
    }

    #[Test]
    public function the_login_and_discover_limiters_also_count_per_account_and_hour(): void
    {
        $login = RateLimiter::limiter('login')(Request::create('/login', 'POST', ['email' => 'Max@Example.com']));
        $this->assertCount(2, $login);
        $this->assertSame('account|max@example.com', $login[1]->key);
        $this->assertSame(10, $login[1]->maxAttempts);

        $discover = RateLimiter::limiter('app-discover')(Request::create('/api/app/v1/auth/discover', 'POST'));
        $this->assertCount(2, $discover);
        $this->assertSame(60, $discover[1]->maxAttempts);
    }

    #[Test]
    public function a_notification_image_url_cannot_point_to_other_public_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('keyVisual/fremd.png', 'x');
        $this->actingAsAdmin();
        $notification = GlobalNotification::factory()->create(['image_name' => '']);

        $this->put(route('global_notification.update', $notification), [
            'notificationName' => 'Hinweis',
            'notificationDescription' => 'Text',
            'notificationImage' => 'https://evil.example/notificationImage/../keyVisual/fremd.png',
            'notificationDeadlineDate' => now()->addDay()->format('Y-m-d'),
            'notificationDeadlineTime' => '12:00',
        ]);

        $this->assertSame('', (string) $notification->fresh()->image_name);

        $notification->update(['image_name' => 'notificationImage/../keyVisual/fremd.png']);
        $this->put(route('global_notification.update', $notification), [
            'notificationName' => 'Hinweis',
            'notificationDescription' => 'Text',
            'notificationDeadlineDate' => now()->addDay()->format('Y-m-d'),
            'notificationDeadlineTime' => '12:00',
        ]);

        Storage::disk('public')->assertExists('keyVisual/fremd.png');
    }

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function colors(): array
    {
        return [
            'short hex' => ['#fff', true],
            'long hex' => ['#A1B2C3', true],
            'alpha hex' => ['#A1B2C3FF', true],
            'empty' => ['', true],
            'css injection' => ['red;background:url(http://intern/)', false],
            'named color' => ['red', false],
            'rgb' => ['rgb(1,2,3)', false],
        ];
    }

    #[Test]
    #[DataProvider('colors')]
    public function only_hex_colors_are_accepted(mixed $color, bool $valid): void
    {
        $this->assertSame($valid, Validator::make(['c' => $color], ['c' => [new HexColorRule()]])->passes());
    }

    #[Test]
    public function project_colors_cannot_inject_css_into_pdf_exports(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['color' => '#112233']);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'color' => '#fff;background:url(http://169.254.169.254/)',
        ])->assertSessionHasErrors('color');

        $this->assertSame('#112233', $project->fresh()->color);
    }

    #[Test]
    public function the_sage_log_csv_neutralises_formula_like_texts(): void
    {
        $log = new SageBookingLog(['type' => 'manual']);
        $entry = new SageBookingLogEntry([
            'action' => 'created',
            'target_type' => 'x',
            'buchungstext' => '=HYPERLINK("http://evil")',
            'buchungsbetrag' => '-12.50',
        ]);

        $row = (new SageBookingLogExport($log))->map($entry);

        $this->assertContains("'=HYPERLINK(\"http://evil\")", $row);
        $this->assertContains('-12.50', $row);
    }

    #[Test]
    public function new_crm_contacts_ignore_a_free_profile_image_path(): void
    {
        $this->actingAsAdmin();
        $type = CrmContactType::factory()->create();

        $this->post(route('crm.contacts.store'), [
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Neu',
            'profile_image' => '../../.env',
        ]);

        $contact = CrmContact::query()->where('display_name', 'Neu')->firstOrFail();
        $this->assertNull($contact->profile_image);
    }

    #[Test]
    public function the_calendar_feed_skips_planning_events_and_its_link_can_be_renewed(): void
    {
        $owner = User::factory()->create();
        $abo = UserCalendarAbo::factory()->create(['user_id' => $owner->id]);
        $planned = Event::factory()->create(['is_planning' => true]);
        $confirmed = Event::factory()->create(['is_planning' => false]);

        $ids = app(UserCalendarAboService::class)->getFilteredEventsQuery($abo)->pluck('id');
        $this->assertTrue($ids->contains($confirmed->id));
        $this->assertFalse($ids->contains($planned->id));

        $oldToken = $abo->calendar_abo_id;
        $this->actingAs(User::factory()->create());
        $this->delete(route('user.calendar.abo.renew', $abo))->assertForbidden();

        $this->actingAs($owner);
        $this->delete(route('user.calendar.abo.renew', $abo))->assertRedirect();
        $this->assertNotSame($oldToken, $abo->fresh()->calendar_abo_id);
    }

    #[Test]
    public function private_contact_data_stays_out_of_the_search_index(): void
    {
        $user = User::factory()->create([
            'email_private' => true,
            'phone_private' => true,
            'phone_number' => '0170 123',
        ]);

        $searchable = $user->toSearchableArray();

        $this->assertNull($searchable['email']);
        $this->assertNull($searchable['phone_number']);
    }

    #[Test]
    public function secrets_are_scrubbed_and_not_flashed(): void
    {
        $scrubbed = SentryEventScrubber::scrub(['client_secret' => 'a', 'bind_password' => 'b', 'name' => 'c']);
        $this->assertSame(SentryEventScrubber::FILTERED, $scrubbed['client_secret']);
        $this->assertSame(SentryEventScrubber::FILTERED, $scrubbed['bind_password']);
        $this->assertSame('c', $scrubbed['name']);

        $dontFlash = (new \ReflectionProperty(Handler::class, 'dontFlash'))->getValue(app(Handler::class));
        $this->assertContains('password_confirmation', $dontFlash);
    }

    #[Test]
    public function queued_shift_broadcasts_store_model_ids_only(): void
    {
        $this->assertContains(SerializesModels::class, class_uses(ShiftAssigned::class));
    }

    #[Test]
    public function the_key_visual_download_needs_project_view_rights(): void
    {
        $project = Project::factory()->create(['key_visual_path' => 'kv.png']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('project.download.keyVisual', $project));

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
