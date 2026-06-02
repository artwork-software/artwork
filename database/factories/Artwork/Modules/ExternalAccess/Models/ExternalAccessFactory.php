<?php

namespace Database\Factories\Artwork\Modules\ExternalAccess\Models;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExternalAccess>
 */
class ExternalAccessFactory extends Factory
{
    protected $model = ExternalAccess::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'crm_contact_id' => function () {
                $slug = 'external-test-' . Str::random(6);
                $type = CrmContactType::firstOrCreate(
                    ['slug' => 'external-test-contact'],
                    ['name' => 'External Test Contact', 'is_system' => false, 'is_active' => true],
                );
                return CrmContact::create([
                    'crm_contact_type_id' => $type->id,
                    'display_name' => $this->faker->name(),
                    'is_active' => true,
                ])->id;
            },
            'invited_by_user_id' => null,
            'crm_access_expires_at' => null,
            'revoked_at' => null,
            'last_login_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state([
            'crm_access_expires_at' => now()->addMonths(12),
            'revoked_at' => null,
        ]);
    }

    public function revoked(): self
    {
        return $this->state([
            'revoked_at' => now(),
        ]);
    }

    public function crmAccessExpired(): self
    {
        return $this->state([
            'crm_access_expires_at' => now()->subDay(),
        ]);
    }
}
