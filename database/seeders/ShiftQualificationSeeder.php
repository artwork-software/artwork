<?php

namespace Database\Seeders;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Illuminate\Database\Seeder;

class ShiftQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ShiftQualification::create([
            'icon' => 'user-icon',
            'name' => 'Mitarbeiter',
            'available' => true
        ]);

        ShiftQualification::create([
            'icon' => 'academic-cap-icon',
            'name' => 'Meister',
            'available' => true
        ]);

        //need some more to test with? do not commit uncommented
        //uncomment the next line and run "sail artisan db:seed --class=ShiftQualificationSeeder"
        //ShiftQualification::factory(50)->create();
    }
}
