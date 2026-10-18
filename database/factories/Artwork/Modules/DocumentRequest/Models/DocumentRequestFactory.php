<?php

namespace Database\Factories\Artwork\Modules\DocumentRequest\Models;

use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRequest>
 */
class DocumentRequestFactory extends Factory
{
    protected $model = DocumentRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
        ];
    }
}
