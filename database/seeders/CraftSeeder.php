<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Seeder;

class CraftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $calderoSystems = Craft::create([
            'name' => 'Caldero-Systems GmbH',
            'abbreviation' => 'CS',
            'assignable_by_all' => false
        ]);

        $calderoSystems->users()->sync([1,2]);

        Craft::create([
            'name' => 'Kampnagel',
            'abbreviation' => 'K',
            'assignable_by_all' => true
        ]);
    }
}
