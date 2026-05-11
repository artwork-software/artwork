<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceReminderControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_reminder(): void
    {
        $source = MoneySource::factory()->create();

        $this->post(route('money_source.reminder.store', ['money_source' => $source->id]), [
            'expirationReminders' => [['days' => 7]],
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_expiration_reminder(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(
            route('money_source.reminder.store', ['money_source' => $source->id]),
            ['expirationReminders' => [['days' => 7]]]
        );

        // Controller returns void, so response is redirect/200
        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_reminders', [
            'money_source_id' => $source->id,
            'value' => 7,
        ]);
    }
}
