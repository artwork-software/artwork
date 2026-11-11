<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class AiConversationWithTurnsData extends Data
{
    /** @param AiConversationTurnData[] $turns */
    public function __construct(
        public AiConversationData $conversation,
        public array $turns,
    ) {
    }

    /** @param Collection<int, \Artwork\Modules\AiAssistant\Models\AiConversationTurn> $turns */
    public static function fromModel(AiConversation $conversation, Collection $turns): self
    {
        return new self(
            conversation: AiConversationData::fromModel($conversation),
            turns: $turns->map(AiConversationTurnData::fromModel(...))->all(),
        );
    }
}
