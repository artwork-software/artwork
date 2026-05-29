<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminBypassProtectionTest extends TestCase
{
    #[Test]
    public function external_access_user_does_not_trigger_admin_gate_bypass(): void
    {
        Gate::define('artwork-test-ability', fn () => false);

        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);
        Auth::shouldUse('external');

        $this->assertFalse(Gate::allows('artwork-test-ability'));
        $this->assertFalse($external->can('artwork-test-ability'));
    }

    #[Test]
    public function internal_admin_still_triggers_gate_bypass(): void
    {
        Gate::define('artwork-test-ability', fn () => false);

        $admin = $this->adminUser();
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('artwork-test-ability'));
    }
}
