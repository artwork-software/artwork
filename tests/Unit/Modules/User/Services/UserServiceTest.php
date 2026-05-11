<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserService::class);
    }

    #[Test]
    public function get_all_users_returns_collection(): void
    {
        User::factory()->count(3)->create();

        $users = $this->service->getAllUsers();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertGreaterThanOrEqual(3, $users->count());
    }

    #[Test]
    public function find_user_returns_user_by_id(): void
    {
        $user = User::factory()->create();

        $found = $this->service->findUser($user->id);

        $this->assertSame($user->id, $found->id);
    }

    #[Test]
    public function get_auth_user_returns_currently_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $auth = $this->service->getAuthUser();

        $this->assertSame($user->id, $auth->id);
    }

    #[Test]
    public function get_auth_user_id_returns_id_of_current_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertSame($user->id, $this->service->getAuthUserId());
    }

    #[Test]
    public function update_modifies_user_attributes(): void
    {
        $user = User::factory()->create(['first_name' => 'Old']);

        $updated = $this->service->update($user, ['first_name' => 'New']);

        $this->assertSame('New', $updated->first_name);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'New']);
    }

    #[Test]
    public function update_accepts_int_user_id(): void
    {
        $user = User::factory()->create(['first_name' => 'Foo']);

        $updated = $this->service->update($user->id, ['first_name' => 'Bar']);

        $this->assertSame('Bar', $updated->first_name);
    }

    #[Test]
    public function update_current_user_show_notification_indicator_flips_flag(): void
    {
        $user = User::factory()->create(['show_notification_indicator' => false]);

        $this->service->updateCurrentUserShowNotificationIndicator($user, true);

        $this->assertTrue((bool) $user->fresh()->show_notification_indicator);
    }

    #[Test]
    public function search_users_returns_support_collection(): void
    {
        $result = $this->service->searchUsers('something');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    #[Test]
    public function get_user_calendar_filter_dates_or_default_falls_back_to_now(): void
    {
        $filter = new \Artwork\Modules\User\Models\UserFilter();
        $filter->start_date = null;
        $filter->end_date = null;

        [$start, $end] = $this->service->getUserCalendarFilterDatesOrDefault($filter);

        $this->assertInstanceOf(\Carbon\Carbon::class, $start);
        $this->assertInstanceOf(\Carbon\Carbon::class, $end);
        $this->assertTrue($end->greaterThan($start));
    }
}
