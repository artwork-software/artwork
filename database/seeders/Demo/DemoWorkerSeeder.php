<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Legt die Demo-Belegschaft an: User (mit Einheitspasswort und Rechten),
 * Freelancer und Dienstleister. Idempotent über E-Mail bzw. Namen.
 * Die Verknüpfung mit Gewerken/Verträgen macht der DemoWorkerLinkSeeder.
 */
class DemoWorkerSeeder extends Seeder
{
    public function run(): void
    {
        $createdUsers = $this->seedUsers();
        $createdFreelancers = $this->seedFreelancers();
        $createdProviders = $this->seedServiceProviders();

        $this->command?->info(sprintf(
            'Belegschaft: %d User, %d Freelancer, %d Dienstleister neu angelegt.',
            $createdUsers,
            $createdFreelancers,
            $createdProviders
        ));
        if ($createdUsers > 0) {
            $this->command?->warn(sprintf(
                'Login aller Demo-User: vorname.nachname@%s / Passwort: %s',
                DemoDataPools::EMAIL_DOMAIN,
                DemoDataPools::DEMO_PASSWORD
            ));
        }
    }

    private function seedUsers(): int
    {
        $created = 0;
        foreach (DemoDataPools::USERS as $entry) {
            $email = DemoDataPools::email($entry['first'], $entry['last']);
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            $weeklyHours = DemoDataPools::CONTRACTS[$entry['contract']]['weekly_hours'] ?? 40.0;
            $user = User::create([
                'first_name' => $entry['first'],
                'last_name' => $entry['last'],
                'email' => $email,
                'password' => Hash::make(DemoDataPools::DEMO_PASSWORD),
                'position' => $entry['position'],
                'pronouns' => $entry['pronouns'] ?? null,
                'business' => DemoDataPools::COMPANY_NAME,
                'description' => null,
                'language' => 'de',
                'can_work_shifts' => $entry['craft'] !== null,
                'weekly_working_hours' => $weeklyHours,
                'employStart' => Carbon::now()->subYears(2)->startOfYear(),
                'toggle_hints' => false,
                'opened_checklists' => [],
                'opened_areas' => [],
            ]);
            $created++;

            $this->seedUserScaffolding($user);
            $this->seedPermissions($user, $entry);
        }

        return $created;
    }

    /** Gleiches Grundgerüst wie der AuthUserSeeder: Notification-Settings, Kalender-Settings, Filter. */
    private function seedUserScaffolding(User $user): void
    {
        foreach (NotificationEnum::cases() as $notificationType) {
            $user->notificationSettings()->create([
                'group_type' => $notificationType->groupType(),
                'type' => $notificationType->value,
                'title' => $notificationType->title(),
                'description' => $notificationType->description(),
            ]);
        }

        $user->calendar_settings()->create();

        foreach (
            [
                UserFilterTypes::CALENDAR_FILTER,
                UserFilterTypes::PLANNING_FILTER,
                UserFilterTypes::SHIFT_FILTER,
            ] as $filterType
        ) {
            $user->userFilters()->create([
                'filter_type' => $filterType->value,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addWeeks(2)->endOfDay(),
            ]);
        }
    }

    /** @param array<string, mixed> $entry */
    private function seedPermissions(User $user, array $entry): void
    {
        if ($entry['admin'] ?? false) {
            $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

            return;
        }

        $permissions = [
            PermissionEnum::PROJECT_VIEW->value,
            PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
            PermissionEnum::EVENT_REQUEST->value,
            PermissionEnum::CONTRACT_SEE_DOWNLOAD->value,
        ];
        if ($entry['planner'] ?? false) {
            $permissions[] = PermissionEnum::SHIFT_PLANNER->value;
            $permissions[] = PermissionEnum::ROOM_UPDATE->value;
        }
        $user->givePermissionTo($permissions);
    }

    private function seedFreelancers(): int
    {
        $created = 0;
        foreach (DemoDataPools::FREELANCERS as $entry) {
            $email = DemoDataPools::email($entry['first'], $entry['last']);
            $freelancer = Freelancer::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $entry['first'],
                    'last_name' => $entry['last'],
                    'position' => $entry['position'],
                    'salary_per_hour' => $entry['salary'],
                    'salary_description' => 'Tagessatz nach Vereinbarung, Abrechnung über Honorarvertrag.',
                    'can_work_shifts' => true,
                ]
            );
            if ($freelancer->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    private function seedServiceProviders(): int
    {
        $created = 0;
        foreach (DemoDataPools::SERVICE_PROVIDERS as $entry) {
            $provider = ServiceProvider::firstOrCreate(
                ['provider_name' => $entry['name']],
                [
                    'email' => $entry['email'],
                    'phone_number' => $entry['phone'],
                    'note' => $entry['note'],
                    'type_of_provider' => $entry['type'],
                    'can_work_shifts' => $entry['craft'] !== null,
                ]
            );
            if ($provider->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
