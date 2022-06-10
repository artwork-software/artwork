<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('projects')->insert([
            'name' => 'TestProject',
            'description' => null,
            'number_of_participants' => null,
            'cost_center' => null,
            'sector_id' => null,
            'category_id' => null,
            'genre_id' => null
        ]);
    }
}
