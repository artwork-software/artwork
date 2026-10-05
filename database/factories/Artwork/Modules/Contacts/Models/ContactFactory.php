<?php

namespace Database\Factories;

use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Contacts\Models\Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contactable_type' => User::class,
            'contactable_id' => User::factory(),
            'name' => $this->faker->name(),
            'street' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'location' => $this->faker->city(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'fax' => null,
            'is_primary' => false,
        ];
    }
}
