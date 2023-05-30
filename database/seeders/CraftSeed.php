<?php

namespace Database\Seeders;

use App\Models\Craft;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CraftSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
