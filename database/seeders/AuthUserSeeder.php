<?php

namespace Database\Seeders;

use App\Enums\NotificationConstEnum;
use App\Models\Checklist;
use App\Models\Department;
use App\Models\GeneralSettings;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Storage::put('/public/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
            File::get(public_path('/profile-photos/photo-1499996860823-5214fcc65f8f.jpg')), 'public');
        $this->command->info("Profile Photo set");

        $user = User::create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max.mustermann@kampnagel.de',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Administrator',
            'business' => 'Kampnagel',
            'description' => null,
            'toggle_hints' => true,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg'
        ]);

        foreach (NotificationConstEnum::cases() as $notificationType) {

            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);

        }

        $user->assignRole('admin');

        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();
    }
}
