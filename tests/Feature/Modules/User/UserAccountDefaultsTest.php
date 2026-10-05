<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserAccountDefaultsTest extends FeatureTestCase
{
    #[Test]
    public function it_creates_all_account_defaults_for_a_bare_user(): void
    {
        $user = User::factory()->create();

        app(UserService::class)->initializeAccountDefaults($user);

        $this->assertTrue($user->calendar_settings()->exists());
        foreach (
            [
                UserFilterTypes::CALENDAR_FILTER,
                UserFilterTypes::PLANNING_FILTER,
                UserFilterTypes::SHIFT_FILTER,
            ] as $filterType
        ) {
            $this->assertSame(1, $user->userFilters()->where('filter_type', $filterType->value)->count());
        }
        $this->assertSame(count(NotificationEnum::cases()), $user->notificationSettings()->count());
        $this->assertSame(1, $user->productBasket()->count());
        $this->assertNotNull(app(UserUserManagementSettingService::class)->getFromUser($user));
    }

    #[Test]
    public function it_is_idempotent_and_keeps_existing_values(): void
    {
        $user = User::factory()->create();
        $service = app(UserService::class);
        $service->initializeAccountDefaults($user);

        $user->calendar_settings()->update(['use_project_time_period' => true]);
        $managementSettingService = app(UserUserManagementSettingService::class);
        $managementSettingService->updateOrCreateIfNecessary($user, ['custom' => true]);
        $user->notificationSettings()->first()->update(['enabled_email' => false]);

        $service->initializeAccountDefaults($user);

        $this->assertSame(1, $user->calendar_settings()->count());
        $this->assertTrue((bool) $user->calendar_settings()->value('use_project_time_period'));
        $this->assertSame(['custom' => true], $managementSettingService->getFromUser($user)->settings);
        $this->assertSame(count(NotificationEnum::cases()), $user->notificationSettings()->count());
        $this->assertSame(1, $user->notificationSettings()->where('enabled_email', false)->count());
        $this->assertSame(
            1,
            $user->userFilters()->where('filter_type', UserFilterTypes::CALENDAR_FILTER->value)->count()
        );
        $this->assertSame(1, $user->productBasket()->count());
    }

    #[Test]
    public function the_update_command_completes_accounts_without_calendar_settings(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->calendar_settings()->exists());

        $command = app(\Artwork\Core\Console\Commands\UpdateArtwork::class);
        $method = new \ReflectionMethod($command, 'initializeMissingAccountDefaults');
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput()
        ));
        $method->invoke($command);

        $this->assertTrue($user->calendar_settings()->exists());
        $this->assertTrue($user->userFilters()->calendarFilter()->exists());
    }
}
