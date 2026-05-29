<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    #[Test]
    public function logout_invalidates_session(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $this->post(route('external.logout'));

        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function logout_redirects_to_login(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        Auth::guard('external')->login($external);

        $response = $this->post(route('external.logout'));

        $response->assertRedirect(route('external.login.form'));
    }
}
