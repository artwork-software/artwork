<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Inventory\Services\CraftItemMigrationService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Services\ProjectManagementBuilderService;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

class UpdateArtwork extends Command
{
    protected $signature = 'artwork:update';
    protected $description = 'Updates Artwork core data and modules';

    public function __construct(
        private readonly ProjectManagementBuilderService $projectManagementBuilderService,
        private readonly CraftItemMigrationService $craftItemMigrationService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('--- Starting Artwork Update ---');

        $this->updateProjectManagementBuilder();
        $this->updatePermissions();
        $this->addNewComponents();
        $this->updateShiftQualificationIcons();
        $this->updateNotificationSettings();
        $this->addProjectGroupColumn();
        $this->updateServiceProviderContacts();
        $this->addInventoryArticleStatus();
        $this->addInventoryArticlePlanFilter();
        $this->migrateCraftInventoryItems();
        $this->setupPassport();
        $this->removeOldCalendarComponent();
        $this->migrateFilterToNewFilterStructure();

        $this->info('--- Artwork Update Finished ---');
    }

    private function updateProjectManagementBuilder(): void
    {
        $this->section('Project Management Builder');
        if ($this->projectManagementBuilderService->getProjectManagementBuilder()->isEmpty()) {
            $this->call('db:seed', ['--class' => 'ProjectManagementBuilderSeed']);
            $this->info('Seed executed');
        } else {
            $this->info('Already seeded');
        }
    }

    private function updatePermissions(): void
    {
        $this->section('Permissions');
        $this->call('artwork:update-permissions');
    }

    private function addNewComponents(): void
    {
        $this->section('New Components');
        $this->call('artwork:add-new-components');
    }

    private function updateShiftQualificationIcons(): void
    {
        $this->section('Shift Qualification Icons');
        $this->call('db:seed', ['--class' => 'ShiftQualificationIconsSeeder', '--force' => true]);
    }

    private function updateNotificationSettings(): void
    {
        $this->section('Notification Settings');

        NotificationSetting::where('type', 'NOTIFICATION_ROOM_ANSWER')->update([
            'title' => 'Room requests answered',
            'description' => 'Find out if your room requests has been answered.',
        ]);

        $users = User::all();
        foreach ($users as $user) {
            $this->addUserNotificationSettings($user);
        }
    }

    private function addUserNotificationSettings(User $user): void
    {
        $notificationTypes = [
            NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS,
            NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED,
            NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED,
            NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_REQUEST_APPROVED,
            NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_REQUEST_DECLINED,
            NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_GET_REQUEST,
            NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST,
        ];

        foreach ($notificationTypes as $enum) {
            $user->notificationSettings()->updateOrCreate(
                ['type' => $enum->value],
                [
                    'frequency' => NotificationFrequencyEnum::DAILY->value,
                    'group_type' => $enum->groupType(),
                    'title' => $enum->title(),
                    'description' => $enum->description(),
                    'enabled_email' => true,
                    'enabled_push' => true,
                ]
            );
        }
    }

    private function addProjectGroupColumn(): void
    {
        $this->section('Project Group Column');
        $this->call('db:seed', ['--class' => 'UpdateOrCreateProjectRelevantColumn', '--force' => true]);
    }

    private function updateServiceProviderContacts(): void
    {
        $this->section('Service Provider Contacts');
        $this->call('artwork:update-service-provider-contacts');
    }

    private function addInventoryArticleStatus(): void
    {
        $this->section('Inventory Article Status');
        $this->call('db:seed', ['--class' => 'InventoryArticleStatusSeeder', '--force' => true]);
    }

    private function addInventoryArticlePlanFilter(): void
    {
        $this->section('Inventory Article Plan Filter');
        User::doesntHave('inventoryArticlePlanFilter')->each(function (User $user): void {
            $user->inventoryArticlePlanFilter()->create([
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);
        });
    }

    private function migrateCraftInventoryItems(): void
    {
        $this->section('Craft Item Migration');
        $result = $this->craftItemMigrationService->migrateCraftItemsToInventoryArticles();
        $this->info("Migrated: {$result['success_count']} items");
        if (!empty($result['skipped_count'])) {
            $this->info("Skipped: {$result['skipped_count']} items");
        }
    }

    private function setupPassport(): void
    {
        $this->section('Passport Setup');

        if (!is_readable(Passport::keyPath('oauth-public.key'))) {
            $this->call('passport:keys', ['--force' => true]);
        }

        if (DB::table('oauth_clients')->count() === 0) {
            $provider = config('auth.providers.users') ? 'users' : null;

            $this->call('passport:client', [
                '--personal' => true,
                '--name' => config('app.name') . ' Personal Access Client'
            ]);

            $this->call('passport:client', [
                '--password' => true,
                '--name' => config('app.name') . ' Password Grant Client',
                '--provider' => $provider,
            ]);
        }
    }

    private function removeOldCalendarComponent(): void
    {
        $this->section('Calendar Component Removal');

        $calendar = Component::where('type', ProjectTabComponentEnum::CALENDAR->value)->first();
        if (!$calendar) {
            $this->info('No calendar component found.');
            return;
        }

        $calendar->users()->detach();
        $calendar->departments()->detach();
        $calendar->sidebarTabComponent()?->delete();
        $calendar->tabComponent()?->delete();
        $calendar->projectValue()?->delete();
        $calendar->componentInDisclosures()?->delete();
        $calendar->componentInPrintLayouts()?->delete();
        $calendar->delete();

        $this->info('Old calendar component has been removed.');
    }

    private function section(string $title): void
    {
        $this->info(str_repeat('-', 60));
        $this->info("[$title]");
    }

    private function migrateFilterToNewFilterStructure(): void
    {
        $this->section('Filter Migration');

        User::with(['calendar_filter', 'shift_calendar_filter'])->chunk(100, function ($users): void {
            foreach ($users as $user) {
                // Calendar Filter migrieren
                if ($user->calendar_filter) {
                    $calendarFilter = $user->calendar_filter;

                    $user->userFilters()->updateOrCreate(
                        ['filter_type' => 'calendar_filter'],
                        [
                            'start_date' => $calendarFilter->start_date ?? now(),
                            'end_date' => $calendarFilter->end_date ?? now()->addMonth(),
                            'event_type_ids' => $calendarFilter->event_types ?? null,
                            'room_ids' => $calendarFilter->rooms ?? null,
                            'area_ids' => $calendarFilter->areas ?? null,
                            'room_attribute_ids' => $calendarFilter->room_attributes ?? null,
                            'room_category_ids' => $calendarFilter->room_categories ?? null,
                            'event_property_ids' => $calendarFilter->event_properties ?? null,
                            'craft_ids' => null, // Keine Datenquelle in deinem Modell
                        ]
                    );

                    $user->userFilters()->updateOrCreate(
                        ['filter_type' => 'planning_filter'],
                        [
                            'start_date' => $calendarFilter->start_date ?? now(),
                            'end_date' => $calendarFilter->end_date ?? now()->addMonth(),
                            'event_type_ids' => $calendarFilter->event_types ?? null,
                            'room_ids' => $calendarFilter->rooms ?? null,
                            'area_ids' => $calendarFilter->areas ?? null,
                            'room_attribute_ids' => $calendarFilter->room_attributes ?? null,
                            'room_category_ids' => $calendarFilter->room_categories ?? null,
                            'event_property_ids' => $calendarFilter->event_properties ?? null,
                            'craft_ids' => null, // Keine Datenquelle in deinem Modell
                        ]
                    );

                    $calendarFilter->delete();
                }

                // Shift Filter migrieren
                if ($user->shift_calendar_filter) {
                    $shiftFilter = $user->shift_calendar_filter;

                    $user->userFilters()->updateOrCreate(
                        ['filter_type' => 'shift_filter'],
                        [
                            'start_date' => $shiftFilter->start_date ?? now(),
                            'end_date' => $shiftFilter->end_date ?? now()->addMonth(),
                            'event_type_ids' => $shiftFilter->event_types ?? null,
                            'room_ids' => $shiftFilter->rooms ?? null,
                            'area_ids' => null,
                            'room_attribute_ids' => null,
                            'room_category_ids' => null,
                            'event_property_ids' => null,
                            'craft_ids' => $user->show_crafts,
                        ]
                    );

                    $shiftFilter->delete();
                }
            }
        });
    }

    private function migrateUserFilter(User $user, string $relation, string $filterType, array $fields): void
    {
        $filter = $user->$relation;

        if (!$filter) {
            return;
        }

        $data = collect($fields)->mapWithKeys(fn($field) => [
            $field =>
                $filter->$field ?? ($field === 'start_date' ? now() : ($field === 'end_date' ? now()->addMonth() : null)),
        ])->toArray();

        $user->userFilters()->updateOrCreate(['filter_type' => $filterType], $data);
    }
}
