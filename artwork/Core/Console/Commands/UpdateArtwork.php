<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectManagementBuilder\Services\ProjectManagementBuilderService;
use Artwork\Modules\User\Models\User;
use Database\Seeders\ProjectManagementBuilderSeed;
use Illuminate\Console\Command;

class UpdateArtwork extends Command
{
    public function __construct(
        private readonly ProjectManagementBuilderService $projectManagementBuilderService,
        private readonly ColumnService $columnService,
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
        $this->call('db:seed', ['--class' => 'ShiftQualificationIconsSeeder']);
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
        });

        $this->info('----------------------------------------------------------');


        // add to all project Groups the new column with type project_relevant_column
        $this->info('Add new column to all project groups');
        $this->call('db:seed', ['--class' => 'UpdateOrCreateProjectRelevantColumn']);
        $this->info('----------------------------------------------------------');
        $this->info('Artwork Update Command has finished');
    }
}
