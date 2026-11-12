<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\User\Models\User;
use Spatie\LaravelData\Data;

class AiContextUserData extends Data
{
    public function __construct(
        public int $id,
        public string $display_name,
    ) {
    }

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            display_name: trim($user->first_name . ' ' . $user->last_name),
        );
    }
}
