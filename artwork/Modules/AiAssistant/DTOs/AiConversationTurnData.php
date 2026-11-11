<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Spatie\LaravelData\Data;

class AiConversationTurnData extends Data
{
    /**
     * @param array<int, array<string, string>> $tool_calls
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $id,
        public string $client_turn_id,
        public string $user_message,
        public ?string $assistant_message,
        public string $status,
        public ?string $error_code,
        public array $tool_calls,
        public array $context,
        public ?string $created_at,
    ) {
    }

    public static function fromModel(AiConversationTurn $turn): self
    {
        return new self(
            id: $turn->id,
            client_turn_id: $turn->client_turn_id,
            user_message: $turn->user_message,
            assistant_message: $turn->assistant_message,
            status: $turn->status,
            error_code: $turn->error_code,
            tool_calls: $turn->tool_calls ?? [],
            context: $turn->context ?? [],
            created_at: $turn->created_at?->toIso8601String(),
        );
    }
}
