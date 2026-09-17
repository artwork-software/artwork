<?php

namespace Tests\Feature\ExternalAccess\Console;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Notifications\ExternalAccessExpiringNotification;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class ExpiryReminderCommandTest extends TestCase
{
    #[Test]
    public function inviter_is_reminded_once_before_scope_and_crm_access_expire(): void
    {
        Notification::fake();
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->create([
            'invited_by_user_id' => $inviter->id,
            'crm_access_expires_at' => now()->addDays(2),
        ]);
        $soon = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'valid_to' => now()->addDays(2),
        ]);
        $later = ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'valid_to' => now()->addDays(30),
        ]);

        $this->artisan('artwork:external-access:expiry-reminders')->assertSuccessful();

        Notification::assertSentToTimes($inviter, ExternalAccessExpiringNotification::class, 2);
        $this->assertNotNull($soon->fresh()->expiry_reminder_sent_at);
        $this->assertNull($later->fresh()->expiry_reminder_sent_at);
        $this->assertNotNull($external->fresh()->crm_expiry_reminder_sent_at);

        // zweiter Lauf: nichts Neues
        $this->artisan('artwork:external-access:expiry-reminders')->assertSuccessful();
        Notification::assertSentToTimes($inviter, ExternalAccessExpiringNotification::class, 2);
    }

    #[Test]
    public function reminders_are_disabled_with_zero_days(): void
    {
        Notification::fake();
        $settings = app(ExternalAccessSettings::class);
        $settings->expiry_reminder_days = 0;
        $settings->save();

        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->create(['invited_by_user_id' => $inviter->id]);
        ExternalAccessScope::factory()->create(['external_access_id' => $external->id, 'valid_to' => now()->addDay()]);

        $this->artisan('artwork:external-access:expiry-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
