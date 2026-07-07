<?php

namespace Tests\Unit\Modules\Filter\Services;

use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FilterServiceTest extends TestCase
{
    private FilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FilterService::class);
    }

    #[Test]
    public function get_personal_filter_returns_user_filter_templates_for_given_user_and_type(): void
    {
        $user = User::factory()->create();
        UserFilterTemplate::create([
            'user_id' => $user->id,
            'name' => 'My Template',
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
        ]);
        UserFilterTemplate::create([
            'user_id' => $user->id,
            'name' => 'Other Template',
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
        ]);

        $result = $this->service->getPersonalFilter($user, UserFilterTypes::CALENDAR_FILTER->value);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('My Template', $result->first()->name);
    }

    #[Test]
    public function get_personal_filter_returns_empty_collection_when_user_has_no_templates(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getPersonalFilter($user);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_personal_filter_falls_back_to_auth_user_when_no_user_given(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserFilterTemplate::create([
            'user_id' => $user->id,
            'name' => 'Auth Template',
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
        ]);

        $result = $this->service->getPersonalFilter();

        $this->assertCount(1, $result);
        $this->assertSame('Auth Template', $result->first()->name);
    }
}
