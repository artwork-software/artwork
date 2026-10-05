<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ServiceProviderShiftQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceProviderShiftQualification>
 */
class ServiceProviderShiftQualificationFactory extends Factory
{
    protected $model = ServiceProviderShiftQualification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_provider_id' => ServiceProvider::factory(),
            'shift_qualification_id' => ShiftQualification::factory(),
        ];
    }
}
