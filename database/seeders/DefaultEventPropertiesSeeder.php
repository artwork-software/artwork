<?php

namespace Database\Seeders;

use Artwork\Modules\EventProperty\Services\EventPropertyService;
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
        $this->eventPropertyService->create(
            [
                'name' => 'Publikum',
                'icon' => 'users-group'
            ]
        );

        $this->eventPropertyService->create(
            [
                'name' => 'Laut',
                'icon' => 'speakerphone'
            ]
        );
    }
}
