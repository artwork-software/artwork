<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Notification\Services\NotificationSettingsPresenter;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Einstellungsseite: nur Typen, die man bekommen kann, Texte aus dem Enum, Reihenfolge wie im
 * Center, Sammeländerungen und Zurücksetzen.
 */
final class NotificationSettingsPageTest extends FeatureTestCase
{
    private function userWithSettings(string|array $permissions = []): User
    {
        $user = $this->actingAsUserWith($permissions);
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function visibleTypes(User $user): array
    {
        return collect(app(NotificationSettingsPresenter::class)->groupsFor($user))
            ->flatMap(fn (array $group) => array_column($group['settings'], 'type'))
            ->all();
    }

    #[Test]
    public function planner_only_types_and_disabled_modules_are_hidden(): void
    {
        $user = $this->userWithSettings();
        $types = $this->visibleTypes($user);

        $this->assertContains(NotificationEnum::NOTIFICATION_SHIFT_CHANGED->value, $types);
        $this->assertNotContains(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT->value, $types);
        $this->assertNotContains(NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_GET_REQUEST->value, $types);
        $this->assertNotContains(NotificationEnum::NOTIFICATION_REMINDER_ROOM_REQUEST->value, $types);

        $planner = $this->userWithSettings(PermissionEnum::SHIFT_PLANNER->value);
        $this->assertContains(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT->value, $this->visibleTypes($planner));

        $modules = app(ModuleSettings::class);
        $modules->inventory = false;
        $modules->save();
        $external = app(ExternalAccessSettings::class);
        $external->enabled = false;
        $external->save();

        $groups = array_column(app(NotificationSettingsPresenter::class)->groupsFor($planner), 'key');
        $this->assertNotContains(NotificationGroupEnum::INVENTORY->value, $groups);
        $this->assertNotContains(NotificationGroupEnum::EXTERNAL_ACCESS->value, $groups);
    }

    #[Test]
    public function texts_come_from_the_enum_in_the_centre_order(): void
    {
        $user = $this->userWithSettings();
        $user->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)
            ->update(['title' => 'Veralteter Titel']);

        $groups = app(NotificationSettingsPresenter::class)->groupsFor($user);
        $team = collect($groups)->flatMap(fn (array $group) => $group['settings'])
            ->firstWhere('type', NotificationEnum::NOTIFICATION_TEAM->value);

        $this->assertSame(NotificationEnum::NOTIFICATION_TEAM->title(), $team['title']);
        $order = array_map(fn (NotificationGroupEnum $group) => $group->value, NotificationGroupEnum::displayOrder());
        $keys = array_column($groups, 'key');
        $this->assertSame(array_values(array_intersect($order, $keys)), $keys);
    }

    #[Test]
    public function the_page_renders_the_settings_groups(): void
    {
        $this->userWithSettings();

        $this->get(route('notifications.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Show')
                ->has('notificationSettingGroups.0.settings.0.title')
                ->has('notificationFrequencies', count(NotificationFrequencyEnum::cases())));
    }

    #[Test]
    public function bulk_changes_apply_to_a_group_or_all_visible_types(): void
    {
        $user = $this->userWithSettings();

        $this->patchJson(route('notifications.settings.bulk'), [
            'groupType' => NotificationGroupEnum::TASKS->value,
            'enabled_email' => false,
        ])->assertOk()->assertJsonStructure(['groups']);
        $this->assertSame(0, $user->notificationSettings()->where('group_type', 'TASKS')->where('enabled_email', true)->count());
        $this->assertTrue($user->notificationSettings()->where('group_type', 'PROJECTS')->value('enabled_email'));

        $this->patchJson(route('notifications.settings.bulk'), ['frequency' => NotificationFrequencyEnum::WEEKLY_ONCE->value])
            ->assertOk();
        $visible = app(NotificationSettingsPresenter::class)->visibleTypeValuesFor($user);
        $this->assertSame(0, $user->notificationSettings()->whereIn('type', $visible)
            ->where('frequency', '!=', NotificationFrequencyEnum::WEEKLY_ONCE->value)->count());
        // nicht sichtbare Planer-Typen bleiben unberührt
        $this->assertSame(
            NotificationFrequencyEnum::DAILY,
            $user->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT->value)->sole()->frequency
        );

        $this->patchJson(route('notifications.settings.bulk'), ['frequency' => 'hourly'])->assertUnprocessable();
    }

    #[Test]
    public function restoring_defaults_switches_everything_back_on(): void
    {
        $user = $this->userWithSettings();
        $user->notificationSettings()->update([
            'enabled_email' => false,
            'enabled_push' => false,
            'frequency' => NotificationFrequencyEnum::WEEKLY_ONCE->value,
        ]);

        $this->postJson(route('notifications.settings.reset'))->assertOk();

        $this->assertSame(0, $user->notificationSettings()->where('enabled_email', false)->count());
        $this->assertSame(
            NotificationFrequencyEnum::IMMEDIATELY,
            $user->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->value)->sole()->frequency
        );
        $this->assertSame(
            NotificationFrequencyEnum::DAILY,
            $user->notificationSettings()->where('type', NotificationEnum::NOTIFICATION_TEAM->value)->sole()->frequency
        );
    }
}
