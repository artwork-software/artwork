<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Console\Command;

class AddNewComponents extends Command
{
    protected $signature = 'artwork:add-new-components';
    protected $description = 'Add new components to the system';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!Component::query()->where('type', ProjectTabComponentEnum::ARTIST_RESIDENCIES)->first()) {
            Component::create([
                'name' => 'Artist residencies',
                'type' => ProjectTabComponentEnum::ARTIST_RESIDENCIES,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
            $this->info('Artist residencies component added');
        } else {
            $this->info('Artist residencies component already exists');
        }

        if (!Component::query()->where('type', ProjectTabComponentEnum::PROJECT_GROUP_DISPLAY)->first()) {
            Component::create([
                'name' => 'Project group display component',
                'type' => ProjectTabComponentEnum::PROJECT_GROUP_DISPLAY,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
            $this->info('Project group component component added');
        } else {
            $this->info('Project group component component already exists');
        }

        if (!Component::query()->where('type', ProjectTabComponentEnum::GROUP_PROJECT_DISPLAY)->first()) {
            Component::create([
                'name' => 'Component Subprojects',
                'type' => ProjectTabComponentEnum::GROUP_PROJECT_DISPLAY,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
            $this->info('Component Subprojects component added');
        } else {
            $this->info('Component Subprojects component already exists');
        }
        if (!Component::query()->where('type', ProjectTabComponentEnum::ARTIST_NAME_DISPLAY)->first()) {
            Component::create([
                'name' => 'Artist Name Display Component',
                'type' => ProjectTabComponentEnum::ARTIST_NAME_DISPLAY,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
            $this->info('Component Artist Name Display Component added');
        } else {
            $this->info('Component Artist Name Display Component already exists');
        }
    }
}
