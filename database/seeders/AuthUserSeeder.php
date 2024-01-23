<?php

namespace Database\Seeders;

use App\Enums\NotificationConstEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\Craft;
use App\Models\GeneralSettings;
use App\Models\User;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Models\UserShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\UserShiftQualificationRepository;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthUserSeeder extends Seeder
{
    public function __construct(
        private readonly UserShiftQualificationRepository $userShiftQualificationRepository
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Storage::put(
            '/public/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
            File::get(public_path('/profile-photos/photo-1499996860823-5214fcc65f8f.jpg')),
            'public'
        );

        Storage::put(
            '/public/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg',
            File::get(public_path('/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg')),
            'public'
        );

        $user = User::create([
            'first_name' => 'Max',
            'last_name' => 'Schmidt',
            'email' => 'max.mustermann@artwork.software',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Administrator',
            'business' => 'Theater XY',
            'description' => null,
            'toggle_hints' => true,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
            'can_work_shifts' => Factory::create('de_DE')->boolean()
        ]);

        $masterShiftQualificationId = ShiftQualification::query()->masterQualification()->first()->id;
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $masterShiftQualificationId
            ])
        );

        if ($user->can_work_shifts) {
            $user->assignedCrafts()->sync(Craft::all()->pluck('id'));
        }

        foreach (NotificationConstEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);
        }

        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $user->assignRole(RoleNameEnum::ARTWORK_ADMIN->value);

        $user = User::create([
            'first_name' => 'Lisa',
            'last_name' => 'Müller',
            'email' => 'lisa.musterfrau@artwork.software',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Technikerin',
            'business' => 'Museum XY',
            'description' => null,
            'toggle_hints' => true,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg'
        ]);
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $masterShiftQualificationId
            ])
        );
        $user->calendar_settings()->create();
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $user->givePermissionTo([
            PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
            PermissionNameEnum::PROJECT_VIEW->value,
            PermissionNameEnum::EVENT_REQUEST->value,
            PermissionNameEnum::CONTRACT_SEE_DOWNLOAD->value,
            PermissionNameEnum::SHIFT_PLANNER->value,
            //PermissionNameEnum::VIEW_BUDGET_TEMPLATES->value,
        ]);

        foreach (NotificationConstEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);
        }


        $user = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Admin',
            'email' => 'anna.musterfrau@artwork.software',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Chefin',
            'business' => 'Veranstaltungshaus XY',
            'description' => null,
            'toggle_hints' => true,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg'
        ]);
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $masterShiftQualificationId
            ])
        );
        $user->assignRole(RoleNameEnum::ARTWORK_ADMIN->value);
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $user->calendar_settings()->create();
        foreach (NotificationConstEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description()
            ]);
        }


        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();
    }
}
