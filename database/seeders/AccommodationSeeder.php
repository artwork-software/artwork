<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccommodationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic room types - only Einzelzimmer and Doppelzimmer
        \Artwork\Modules\Accommodation\Models\AccommodationRoomType::firstOrCreate([
            'name' => 'Einzelzimmer'
        ]);

        \Artwork\Modules\Accommodation\Models\AccommodationRoomType::firstOrCreate([
            'name' => 'Doppelzimmer'
        ]);
    }
}
