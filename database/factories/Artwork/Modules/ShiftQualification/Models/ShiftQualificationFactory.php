<?php

namespace Database\Factories\Artwork\Modules\ShiftQualification\Models;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShiftQualification>
 */
class ShiftQualificationFactory extends Factory
{
    protected $model = ShiftQualification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $availableIcons = Collection::make([
            //see resources/js/Layouts/Components/ShiftQualificationModal.vue -> shiftQualificationIcons
            'academic-cap-icon',
            'bell-icon',
            'chat-icon',
            'adjustments-icon',
            'book-open-icon',
            'briefcase-icon',
            'camera-icon',
            'clipboard-icon',
            'eye-icon',
            'film-icon'
        ]);

        return [
            'icon' => $availableIcons->random(),
            'name' => Str::ucfirst($this->faker->word),
            'available' => $this->faker->boolean
        ];
    }
}
