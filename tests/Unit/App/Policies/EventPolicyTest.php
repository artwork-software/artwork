<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Policies\EventPolicy;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::EVENT_REQUEST->value);

        $policy = new EventPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value);
        $event = Event::factory()->create(['user_id' => $user->id]);

        $policy = new EventPolicy();

        $this->assertTrue($policy->update($user, $event));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_MANAGEMENT->value);
        $event = Event::factory()->create(['user_id' => $user->id]);

        $policy = new EventPolicy();

        $this->assertTrue($policy->delete($user, $event));
    }
}
