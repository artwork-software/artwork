<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\UserShiftQualification;
use Artwork\Modules\Shift\Repositories\UserShiftQualificationRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthUserSeeder extends Seeder
{
    public function __construct(
        private readonly UserShiftQualificationRepository $userShiftQualificationRepository,
        private readonly UserProjectManagementSettingService $userProjectManagementSettingService,
        private readonly UserUserManagementSettingService $userUserManagementSettingService
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
            'toggle_hints' => false,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
            'can_work_shifts' => true
        ]);

        $this->userProjectManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userProjectManagementSettingService->getDefaults()
        );
        $this->userUserManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userUserManagementSettingService->getDefaults()
        );

        $workerShiftQualificationId = ShiftQualification::workerQualification()->first()->id;
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $workerShiftQualificationId
            ])
        );

        $masterShiftQualificationId = ShiftQualification::masterQualification()->first()->id;
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $masterShiftQualificationId
            ])
        );

        if ($user->can_work_shifts) {
            $user->assignedCrafts()->sync(Craft::all()->pluck('id'));
        }

        foreach (NotificationEnum::cases() as $notificationType) {
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
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $user = User::create([
            'first_name' => 'Lisa',
            'last_name' => 'Müller',
            'email' => 'lisa.musterfrau@artwork.software',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Technikerin',
            'business' => 'Museum XY',
            'description' => null,
            'toggle_hints' => false,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg'
        ]);
        $this->userProjectManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userProjectManagementSettingService->getDefaults()
        );
        $this->userUserManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userUserManagementSettingService->getDefaults()
        );
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
            PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
            //PermissionNameEnum::PROJECT_VIEW->value,
            PermissionEnum::EVENT_REQUEST->value,
            PermissionEnum::CONTRACT_SEE_DOWNLOAD->value,
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value
            //PermissionNameEnum::VIEW_BUDGET_TEMPLATES->value,
        ]);

        foreach (NotificationEnum::cases() as $notificationType) {
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
            'toggle_hints' => false,
            'opened_checklists' => [],
            'opened_areas' => [],
            'profile_photo_path' => '/profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg'
        ]);
        $this->userProjectManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userProjectManagementSettingService->getDefaults()
        );
        $this->userUserManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userUserManagementSettingService->getDefaults()
        );
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $workerShiftQualificationId
            ])
        );
        $this->userShiftQualificationRepository->save(
            new UserShiftQualification([
                'user_id' => $user->id,
                'shift_qualification_id' => $masterShiftQualificationId
            ])
        );
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();
        $user->calendar_settings()->create();
        foreach (NotificationEnum::cases() as $notificationType) {
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
