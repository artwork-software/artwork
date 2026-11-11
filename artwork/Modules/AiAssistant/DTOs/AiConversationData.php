<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Spatie\LaravelData\Data;

class AiConversationData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $status,
        public ?string $last_turn_at,
    ) {
    }

    public static function fromModel(AiConversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            title: $conversation->title,
            status: $conversation->status,
            last_turn_at: $conversation->last_turn_at?->toIso8601String(),
        );
    }
}
