<?php

namespace Database\Seeders;

use Artwork\Modules\Event\Services\EventPropertyService;
use Illuminate\Database\Seeder;
use Throwable;

class DefaultEventPropertiesSeeder extends Seeder
{
    public function __construct(private readonly EventPropertyService $eventPropertyService)
    {
    }

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        if ($this->eventPropertyService->find(1) === null) {
            $this->eventPropertyService->create(
                [
                    'name' => 'Publikum',
                    'icon' => 'IconUsersGroup'
                ]
            );
        }

        if ($this->eventPropertyService->find(2) === null) {
            $this->eventPropertyService->create(
                [
                    'name' => 'Laut',
                    'icon' => 'IconSpeakerphone'
                ]
            );
        }
    }
}
