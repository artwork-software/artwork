<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Invitation\Models\Invitation;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RemoveExpiredInvitationsCommandTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function invitation(string $createdAt, ?string $expiresAt): Invitation
    {
        $invitation = Invitation::factory()->create(['expires_at' => $expiresAt]);
        Invitation::query()->whereKey($invitation->id)->update(['created_at' => $createdAt]);

        return $invitation;
    }

    #[Test]
    public function a_renewed_invitation_is_kept_as_long_as_its_validity_has_not_run_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15 08:00:00'));
        // vor 14 Tagen erstellt, an Tag 10 erneut verschickt: gültig bis Tag 17
        $renewed = $this->invitation('2026-10-01 07:00:00', '2026-10-18 07:00:00');

        $this->artisan('artwork:remove-expired-invitations')->assertSuccessful();

        $this->assertDatabaseHas('invitations', ['id' => $renewed->id]);
    }

    #[Test]
    public function invitations_are_removed_seven_days_after_their_validity_ended(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15 08:00:00'));
        $neverRenewed = $this->invitation('2026-10-01 07:00:00', '2026-10-08 07:00:00');
        $renewedLongAgo = $this->invitation('2026-08-01 07:00:00', '2026-10-08 07:00:00');
        $expiredWithinGracePeriod = $this->invitation('2026-10-02 09:00:00', '2026-10-09 09:00:00');
        $stillValid = $this->invitation('2026-10-12 09:00:00', '2026-10-19 09:00:00');

        $this->artisan('artwork:remove-expired-invitations')->assertSuccessful();

        $this->assertDatabaseMissing('invitations', ['id' => $neverRenewed->id]);
        $this->assertDatabaseMissing('invitations', ['id' => $renewedLongAgo->id]);
        $this->assertDatabaseHas('invitations', ['id' => $expiredWithinGracePeriod->id]);
        $this->assertDatabaseHas('invitations', ['id' => $stillValid->id]);
    }

    #[Test]
    public function invitations_without_an_expiry_date_are_removed_fourteen_days_after_creation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15 08:00:00'));
        $old = $this->invitation('2026-10-01 07:00:00', null);
        $recent = $this->invitation('2026-10-05 07:00:00', null);

        $this->artisan('artwork:remove-expired-invitations')->assertSuccessful();

        $this->assertDatabaseMissing('invitations', ['id' => $old->id]);
        $this->assertDatabaseHas('invitations', ['id' => $recent->id]);
    }
}
