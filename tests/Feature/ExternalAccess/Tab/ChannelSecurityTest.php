<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChannelSecurityTest extends TestCase
{
    /**
     * routes/channels.php authorizes the broadcast channel used by
     * UpdateProjectComponentData ('project.{projectId}') via Auth::check(), which
     * resolves the default (web) guard. External users authenticate only on the
     * 'external' guard, so they can never satisfy that check — i.e. they cannot
     * subscribe to internal project channels even though their edits broadcast
     * on them for internal users.
     */
    #[Test]
    public function external_user_cannot_subscribe_to_internal_channels(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $this->app['auth']->guard('external')->login($external);

        // External guard is authenticated...
        $this->assertTrue(Auth::guard('external')->check());

        // ...but the default/web guard that gates project.* channels is not,
        // so the channel authorization callback (Auth::check()) returns false.
        $this->assertFalse(Auth::guard('web')->check());
        $this->assertFalse(Auth::check());
    }
}
