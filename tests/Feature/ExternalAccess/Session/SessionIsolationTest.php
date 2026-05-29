<?php

namespace Tests\Feature\ExternalAccess\Session;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SessionIsolationTest extends TestCase
{
    #[Test]
    public function external_session_does_not_authenticate_web_guard(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $this->assertTrue(Auth::guard('external')->check());
        $this->assertFalse(Auth::guard('web')->check());
    }

    #[Test]
    public function web_session_does_not_authenticate_external_guard(): void
    {
        $user = User::factory()->create();
        Auth::guard('web')->login($user);

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function parallel_sessions_in_same_browser_work_independently(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $user = User::factory()->create();

        Auth::guard('web')->login($user);
        Auth::guard('external')->login($external);

        $this->assertSame($user->id, Auth::guard('web')->id());
        $this->assertSame($external->id, Auth::guard('external')->id());

        Auth::guard('external')->logout();

        $this->assertSame($user->id, Auth::guard('web')->id());
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function external_and_web_use_different_session_cookies(): void
    {
        $this->assertNotSame(
            config('session.cookie'),
            config('external_access.session.cookie')
        );
    }
}
