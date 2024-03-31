<?php

namespace Database\Seeders;

use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TabContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('de_DE');
        foreach (range(1, 10) as $index) {
            ProjectTab::create([
                'name' => $faker->realText(30),
                'order' => $index,
            ]);
        }

        Component::create([
            'name' => 'Title',
            'type' => TabComponentEnums::TITLE->value,
            'defaults' => [
                'title' => 'Title',
            ],
        ]);

        // seed Text Field
        Component::create([
            'name' => 'Text Feld',
            'type' => TabComponentEnums::TEXT_FIELD->value,
            'defaults' => [],
        ]);


        // checkbox
        Component::create([
            'name' => 'Checkbox',
            'type' => TabComponentEnums::CHECKBOX->value,
            'defaults' => [
                'checked' => false,
            ],
        ]);

        // dropdown
        Component::create([
            'name' => 'Dropdown',
            'type' => TabComponentEnums::DROPDOWN->value,
            'defaults' => [
                'options' => [
                    'Option 1',
                    'Option 2',
                    'Option 3',
                ],
                'selected' => 'Option 1',
            ],
        ]);


        Component::create([
            'name' => 'Text Area',
            'type' => TabComponentEnums::TEXT_AREA->value,
            'defaults' => [],
        ]);
    }
}
