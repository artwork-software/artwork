<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Illuminate\Database\Seeder;

class BudgetColumnSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'column_position' => 0,
                'column_name' => 'KTO',
            ],
            [
                'column_position' => 1,
                'column_name' => 'KST',
            ],
            [
                'column_position' => 2,
                'column_name' => 'Position',
            ],
        ];

        foreach ($settings as $setting) {
            // Check if a setting with this position already exists
            $existingSetting = BudgetColumnSetting::where('column_position', $setting['column_position'])->first();

            if (!$existingSetting) {
                // Create a new model instance
                $budgetColumnSetting = new BudgetColumnSetting();
                // Use forceFill to set both column_name and column_position
                $budgetColumnSetting->forceFill($setting);
                $budgetColumnSetting->save();
            }
        }
    }
}
