<?php

namespace Database\Factories\Artwork\Modules\ExternalAccess\Models;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalInvitation>
 */
class ExternalInvitationFactory extends Factory
{
    protected $model = ExternalInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_access_id' => fn () => ExternalAccess::factory()->active()->create()->id,
            'invited_by_user_id' => fn () => User::factory()->create()->id,
            'source' => 'crm_index',
            'source_reference_id' => null,
            'email_sent_at' => now(),
            'first_redeemed_at' => null,
        ];
    }

    public function fromProjectTab(int $projectId): self
    {
        return $this->state([
            'source' => 'project_tab',
            'source_reference_id' => $projectId,
        ]);
    }

    public function redeemed(): self
    {
        return $this->state([
            'first_redeemed_at' => now(),
        ]);
    }
}
