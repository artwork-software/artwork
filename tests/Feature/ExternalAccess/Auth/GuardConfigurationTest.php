<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GuardConfigurationTest extends TestCase
{
    #[Test]
    public function external_guard_is_registered(): void
    {
        $guard = Auth::guard('external');

        $this->assertInstanceOf(Guard::class, $guard);
        $this->assertInstanceOf(StatefulGuard::class, $guard);
    }

    #[Test]
    public function external_guard_uses_external_access_model(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $retrieved = Auth::guard('external')->user();

        $this->assertInstanceOf(ExternalAccess::class, $retrieved);
        $this->assertSame($external->id, $retrieved->id);
    }

    #[Test]
    public function external_session_cookie_differs_from_web(): void
    {
        $webCookie = config('session.cookie');
        $externalCookie = config('external_access.session.cookie');

        $this->assertNotSame($webCookie, $externalCookie);
        $this->assertSame('artwork_external_session', $externalCookie);
    }
}
