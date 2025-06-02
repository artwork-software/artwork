<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Inventory\Services\CraftItemMigrationService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\ProjectManagementBuilder\Services\ProjectManagementBuilderService;
use Artwork\Modules\User\Models\User;
use Database\Seeders\ProjectManagementBuilderSeed;
use Illuminate\Console\Command;
use Laravel\Passport\Passport;

class UpdateArtwork extends Command
{
    public function __construct(
        private readonly ProjectManagementBuilderService $projectManagementBuilderService,
        private readonly ColumnService $columnService,
        private readonly CraftItemMigrationService $craftItemMigrationService,
    ) {
        parent::__construct();
    }

    protected $signature = 'artwork:update';

    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Artwork Update Command is running');

        if ($this->projectManagementBuilderService->getProjectManagementBuilder()->isEmpty()) {
            $this->call(ProjectManagementBuilderSeed::class);
            $this->info('----------------------------------------------------------');
            $this->info('Project Management Builder Seed has been called');
        } else {
            $this->info('----------------------------------------------------------');
            $this->info('Project Management Builder Seed already exists');
        }
        $this->info('----------------------------------------------------------');

        $this->info('Permissions Update Command is running');
        $this->call('artwork:update-permissions');
        $this->info('Permissions Update Command has been called');
        $this->info('----------------------------------------------------------');

        $this->info('Artwork Add New Components Command is running');
        $this->call('artwork:add-new-components');
        $this->info('Artwork Add New Components Command has been called');
        $this->info('----------------------------------------------------------');

        $this->info('Update Shift-Qualification-Icons');
        $this->call('db:seed', ['--class' => 'ShiftQualificationIconsSeeder', '--force' => true]);
        $this->info('----------------------------------------------------------');


        $this->info('Change Notification Settings');

        NotificationSetting::where('type', 'NOTIFICATION_ROOM_ANSWER')->update([
            'title' => 'Room requests answered',
            'description' => 'Find out if your room requests has been answered.',
        ]);

        // add new Notification type NOTIFICATION_EVENT_VERIFICATION_REQUESTS to NotificationSettings
        $users = User::all();
        $users->each(function ($user) {
            $user->notificationSettings()->updateOrCreate([
                'type' => NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS->value,
            ], [
                'frequency' => NotificationFrequencyEnum::DAILY->value,
                'group_type' => 'EVENTS',
                'title' => NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS->title(),
                'description' => NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS->description(),
                'enabled_email' => true,
                'enabled_push' => true,
            ]);
            // Neue Notification-Typen für Inventory
            foreach ([
                NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED,
                NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED
            ] as $enum) {
                $user->notificationSettings()->updateOrCreate([
                    'type' => $enum->value,
                ], [
                    'frequency' => NotificationFrequencyEnum::DAILY->value,
                    'group_type' => $enum->groupType(),
                    'title' => $enum->title(),
                    'description' => $enum->description(),
                    'enabled_email' => true,
                    'enabled_push' => true,
                ]);
            }
        });

        $this->info('----------------------------------------------------------');


        // add to all project Groups the new column with type project_relevant_column
        $this->info('Add new column to all project groups');
        $this->call('db:seed', ['--class' => 'UpdateOrCreateProjectRelevantColumn', '--force' => true]);
        $this->info('----------------------------------------------------------');

        $this->info('Change service provider contacts to the new contact model structure');
        $this->call('artwork:update-service-provider-contacts');

        $this->info('----------------------------------------------------------');
        // add to all project Groups the new column with type project_relevant_column
        $this->info('add basic inventory article status');
        $this->call('db:seed', ['--class' => 'InventoryArticleStatusSeeder', '--force' => true]);

        $this->info('----------------------------------------------------------');
        // add inventory article plan filter to all users from today + 1 month
        $this->info('add inventory article plan filter to all users');
        $users = User::all();
        $users->each(function ($user) {
            // if allready exists, skip
            if ($user->inventoryArticlePlanFilter) {
                return;
            }
            $user->inventoryArticlePlanFilter()->create([
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);
        });

        $this->info('----------------------------------------------------------');
        $this->info('Migrating craft inventory items to inventory articles');
        $result = $this->craftItemMigrationService->migrateCraftItemsToInventoryArticles();
        $this->info("Migration completed!");
        $this->info("Successfully migrated: {$result['success_count']} items");
        if (isset($result['skipped_count']) && $result['skipped_count'] > 0) {
            $this->info("Skipped: {$result['skipped_count']} items (already migrated)");
        }

        $this->info('----------------------------------------------------------');
        $this->info('Artwork Update Command has finished');
        //Setup Passport
        $this->info('----------------------------------------------------------');
        $this->info('Setting up Laravel Passport if not already set up');
        if (!is_readable(Passport::keyPath('oauth-public.key'))) {
            $this->info('Laravel Passport is not set up, creating');
            $this->call('passport:keys', ['--force' => true]);
        }
    }
}
