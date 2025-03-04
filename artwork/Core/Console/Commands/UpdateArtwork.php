<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\ProjectManagementBuilder\Services\ProjectManagementBuilderService;
use Database\Seeders\ProjectManagementBuilderSeed;
use Illuminate\Console\Command;

class UpdateArtwork extends Command
{
    public function __construct(
        private readonly ProjectManagementBuilderService $projectManagementBuilderService
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
        $this->info('Artwork Update Command has finished');

        $this->info('Artwork add default event properties');
        $this->call('db:seed', ['--class' => 'DefaultEventPropertiesSeeder']);
        $this->info('----------------------------------------------------------');
        $this->info('Artwork Update Command has finished');

        $this->info('Update Shift-Qualification-Icons');
        $this->call('db:seed', ['--class' => 'ShiftQualificationIconsSeeder']);
        $this->info('----------------------------------------------------------');

        $this->info('Change Notification Settings');

        NotificationSetting::where('type', 'NOTIFICATION_ROOM_ANSWER')->update([
            'title' => 'Room requests answered',
            'description' => 'Find out if your room requests has been answered.',
        ]);

        $this->info('----------------------------------------------------------');
    }
}
