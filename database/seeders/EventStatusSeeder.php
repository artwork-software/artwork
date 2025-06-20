<?php

namespace Database\Seeders;

use Artwork\Modules\Event\Models\EventStatus;
use Illuminate\Database\Seeder;

class EventStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Ohne Status',
                'color' => '#a7a6b1',
                'order' => 1,
                'default' => true,
            ],
        ];

        foreach ($statuses as $status) {
            // Check if a status with this name already exists
            $existingStatus = EventStatus::where('name', $status['name'])->first();

            if (!$existingStatus) {
                // Create only if it doesn't exist
                EventStatus::create($status);
            }
        }
    }
}
