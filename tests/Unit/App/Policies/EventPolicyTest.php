<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use Artwork\Modules\Event\Models\Event;
use App\Policies\EventPolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::EVENT_REQUEST->value);

        $policy = new EventPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_MANAGEMENT->value);
        $event = Event::factory()->create(['user_id' => $user->id]);

        $policy = new EventPolicy();

        $this->assertTrue($policy->update($user, $event));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_MANAGEMENT->value);
        $event = Event::factory()->create(['user_id' => $user->id]);

        $policy = new EventPolicy();

        $this->assertTrue($policy->delete($user, $event));
    }
}
