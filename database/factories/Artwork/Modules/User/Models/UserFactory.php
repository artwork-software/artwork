<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use App\Models\Team;
use Artwork\Modules\Permission\Enums\ConsoleOnlyPermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'position' => $this->faker->jobTitle(),
            'business' => $this->faker->domainName(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'opened_checklists' => [],
            'opened_areas' => [],
            'inventory_grid_layout' => true,
        ];
    }

    public function unverified(): Factory
    {
        return $this->state(function () {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Vergibt das nur per Konsole setzbare Recht "ai access" (Schranke vor Maschinen-API und KI-Routen).
     */
    public function withAiAccess(): static
    {
        return $this->afterCreating(static function (User $user): void {
            $permission = Permission::findOrCreate(ConsoleOnlyPermissionEnum::AiAccess->value, 'web');
            $user->givePermissionTo($permission);
        });
    }

    public function withPersonalTeam(): static
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Team::factory()
                //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
                ->state(function (array $attributes, User $user) {
                    return [
                        'first_name' => $user->first_name . '\'s Team',
                        'user_id' => $user->id, 'personal_team' => true
                    ];
                }),
            'ownedTeams'
        );
    }
}
