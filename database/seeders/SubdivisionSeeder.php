<?php

namespace Database\Seeders;

use Artwork\Modules\Holidays\Models\Subdivision;
use Illuminate\Database\Seeder;

class SubdivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (Subdivision::where('code', Subdivision::BRANDENBURG)->first()) {
            return;
        }

        foreach (Subdivision::codes() as $name => $code) {
            Subdivision::create([
                'name' => $name,
                'code' => $code,
                'country_code' => 'DE'
            ]);
        }
    }
}
