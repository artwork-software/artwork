<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\SageApiSettings\Policies\SageApiSettingsPolicy;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class SageApiSettingsPolicyTest extends TestCase
{
    use WithoutMiddleware;

    public function testView(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::SETTINGS_UPDATE->value);

        $policy = new SageApiSettingsPolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testUpdateInterfaceSettings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::SETTINGS_UPDATE->value);

        $policy = new SageApiSettingsPolicy();

        $this->assertTrue($policy->updateInterfaceSettings($user));
    }
}
