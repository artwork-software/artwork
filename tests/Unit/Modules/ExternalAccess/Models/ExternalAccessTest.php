<?php

namespace Tests\Unit\Modules\ExternalAccess\Models;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalAccessTest extends TestCase
{
    #[Test]
    public function has_any_active_access_returns_false_when_revoked(): void
    {
        $external = ExternalAccess::factory()->active()->revoked()->create();

        $this->assertFalse($external->hasAnyActiveAccess());
    }

    #[Test]
    public function has_any_active_access_returns_true_with_crm_access(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        $this->assertTrue($external->hasAnyActiveAccess());
    }

    #[Test]
    public function has_any_active_access_returns_true_with_active_scope(): void
    {
        $external = ExternalAccess::factory()->create();
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $this->assertTrue($external->hasAnyActiveAccess());
    }

    #[Test]
    public function has_any_active_access_returns_false_when_all_expired(): void
    {
        $external = ExternalAccess::factory()->crmAccessExpired()->create();
        ExternalAccessScope::factory()->expired()->create([
            'external_access_id' => $external->id,
        ]);

        $this->assertFalse($external->hasAnyActiveAccess());
    }

    #[Test]
    public function is_crm_access_active_returns_true_when_future_expiry(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        $this->assertTrue($external->isCrmAccessActive());
    }

    #[Test]
    public function is_crm_access_active_returns_false_when_revoked(): void
    {
        $external = ExternalAccess::factory()->active()->revoked()->create();

        $this->assertFalse($external->isCrmAccessActive());
    }

    #[Test]
    public function route_notification_for_mail_returns_email(): void
    {
        $external = ExternalAccess::factory()->create(['email' => 'foo@example.com']);

        $this->assertSame('foo@example.com', $external->routeNotificationForMail());
    }

    #[Test]
    public function get_auth_identifier_returns_id(): void
    {
        $external = ExternalAccess::factory()->create();

        $this->assertSame('id', $external->getAuthIdentifierName());
        $this->assertSame($external->id, $external->getAuthIdentifier());
    }

    #[Test]
    public function remember_token_is_disabled(): void
    {
        $external = ExternalAccess::factory()->create();

        $this->assertNull($external->getRememberToken());
        $external->setRememberToken('foo');
        $this->assertNull($external->getRememberToken());
        $this->assertSame('', $external->getRememberTokenName());
    }
}
