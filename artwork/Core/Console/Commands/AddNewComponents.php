<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentPermissionEnum;
use Artwork\Modules\ProjectTab\Models\Component;
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
    }
}
