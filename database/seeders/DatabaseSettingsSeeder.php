<?php

namespace Database\Seeders;

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
            [
                'group' => 'general',
                'name' => 'allowed_project_file_mimetypes',
                'locked' => 0,
                'payload' => json_encode(['*']),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_room_file_mimetypes',
                'locked' => 0,
                'payload' => json_encode(['*']),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_branding_file_mimetypes',
                'locked' => 0,
                'payload' => json_encode(['*']),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_contract_file_mimetypes',
                'locked' => 0,
                'payload' => json_encode(['*']),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_project_file_size',
                'locked' => 0,
                'payload' => json_encode(150),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_room_file_size',
                'locked' => 0,
                'payload' => json_encode(150),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_branding_file_size',
                'locked' => 0,
                'payload' => json_encode(150),
            ],
            [
                'group' => 'general',
                'name' => 'allowed_contract_file_size',
                'locked' => 0,
                'payload' => json_encode(150),
            ],
        ];

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
