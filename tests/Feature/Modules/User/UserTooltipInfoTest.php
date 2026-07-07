<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserTooltipInfoTest extends FeatureTestCase
{
    private function makeTooltipTarget(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'phone_number' => '+49 40 123456',
            'email_private' => false,
            'phone_private' => false,
        ], $attributes));
    }

    #[Test]
    public function it_returns_tooltip_data_for_an_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());
        $target = $this->makeTooltipTarget();

        $this->getJson(route('user.tooltip.info', $target))
            ->assertSuccessful()
            ->assertJson([
                'id' => $target->id,
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'phone_number' => '+49 40 123456',
                'email_private' => false,
                'phone_private' => false,
            ]);
    }

    #[Test]
    public function it_hides_private_contact_data_from_users_without_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $target = $this->makeTooltipTarget([
            'email_private' => true,
            'phone_private' => true,
        ]);

        $this->getJson(route('user.tooltip.info', $target))
            ->assertSuccessful()
            ->assertJson([
                'email' => null,
                'phone_number' => null,
                'email_private' => true,
                'phone_private' => true,
            ]);
    }

    #[Test]
    public function it_shows_private_contact_data_to_users_with_permission(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO]);
        $target = $this->makeTooltipTarget([
            'email_private' => true,
            'phone_private' => true,
        ]);

        $this->getJson(route('user.tooltip.info', $target))
            ->assertSuccessful()
            ->assertJson([
                'email' => $target->email,
                'phone_number' => '+49 40 123456',
            ]);
    }

    #[Test]
    public function it_shows_private_contact_data_to_admins(): void
    {
        $this->actingAsAdmin();
        $target = $this->makeTooltipTarget([
            'email_private' => true,
            'phone_private' => true,
        ]);

        $this->getJson(route('user.tooltip.info', $target))
            ->assertSuccessful()
            ->assertJson([
                'email' => $target->email,
            ]);
    }

    #[Test]
    public function it_rejects_guests(): void
    {
        $target = $this->makeTooltipTarget();

        $this->getJson(route('user.tooltip.info', $target))
            ->assertUnauthorized();
    }
}
