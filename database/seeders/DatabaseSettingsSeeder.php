<?php

namespace Database\Seeders;

use Artwork\Core\FileHandling\Upload\UploadSettingDefaults;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Legt nur fehlende Einstellungen an; bestehende Upload-Allowlists setzt die Migration
        // reset_upload_allowlists_to_defaults einmalig auf die Standardwerte.
        $settings = [
            [
                'group' => 'general',
                'name' => 'budget_account_management_global',
                'locked' => 0,
                'payload' => json_encode(false),
            ],
            [
                'group' => 'general',
                'name' => 'invitation_email',
                'locked' => 0,
                'payload' => json_encode(''),
            ],
            [
                'group' => 'general',
                'name' => 'business_email',
                'locked' => 0,
                'payload' => json_encode(''),
            ],
        ];

        foreach (UploadSettingDefaults::all() as $name => $value) {
            $settings[] = [
                'group' => UploadSettingDefaults::SETTINGS_GROUP,
                'name' => $name,
                'locked' => 0,
                'payload' => json_encode($value),
            ];
        }

        foreach ($settings as $setting) {
            // Check if the setting already exists
            $existingSetting = DB::table('settings')
                ->where('group', $setting['group'])
                ->where('name', $setting['name'])
                ->first();

            if (!$existingSetting) {
                // Add timestamps
                $setting['created_at'] = Carbon::now();
                $setting['updated_at'] = Carbon::now();

                // Create only if it doesn't exist
                DB::table('settings')->insert($setting);
            }
        }
    }
}
