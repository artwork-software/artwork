<?php

namespace Database\Factories\Artwork\Modules\ExternalAccess\Models;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalAccessScope>
 */
class ExternalAccessScopeFactory extends Factory
{
    protected $model = ExternalAccessScope::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_access_id' => fn () => ExternalAccess::factory()->active()->create()->id,
            'project_id' => fn () => Project::factory()->create()->id,
            'project_tab_id' => fn () => ProjectTab::factory()->create()->id,
            'access_type' => ExternalAccessType::READ->value,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addMonths(3),
            'granted_by_user_id' => null,
        ];
    }

    public function write(): self
    {
        return $this->state(['access_type' => ExternalAccessType::WRITE->value]);
    }

    public function expired(): self
    {
        return $this->state([
            'valid_from' => now()->subMonths(3),
            'valid_to' => now()->subDay(),
        ]);
    }

    public function notYetValid(): self
    {
        return $this->state([
            'valid_from' => now()->addDay(),
            'valid_to' => now()->addMonths(3),
        ]);
    }
}
