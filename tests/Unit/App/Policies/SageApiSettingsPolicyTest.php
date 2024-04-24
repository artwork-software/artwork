<?php

namespace Tests\Unit\App\Policies;

use App\Enums\PermissionNameEnum;
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
        $user->givePermissionTo(PermissionNameEnum::SETTINGS_UPDATE->value);

        $policy = new SageApiSettingsPolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testUpdateInterfaceSettings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::SETTINGS_UPDATE->value);

        $policy = new SageApiSettingsPolicy();

        $this->assertTrue($policy->updateInterfaceSettings($user));
    }
}
