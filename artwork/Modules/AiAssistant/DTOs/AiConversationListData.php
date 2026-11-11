<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/**
 * Gespraechsliste der KI-Bar (core-chat-historie / core-openapi-contract).
 *
 * Eigene Data-Klasse statt response()->json([...]): scramble leitet Schemata aus Typen ab, ein
 * rohes Array liefert ein leeres Schema und macht den Contract an dieser Stelle wertlos (0.4 §5).
 */
class AiConversationListData extends Data
{
    /** @param AiConversationData[] $data */
    public function __construct(
        public array $data,
        public int $current_page,
        public int $last_page,
        public int $per_page,
        public int $total,
    ) {
    }

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            data: $paginator->getCollection()
                ->map(AiConversationData::fromModel(...))
                ->all(),
            current_page: $paginator->currentPage(),
            last_page: $paginator->lastPage(),
            per_page: $paginator->perPage(),
            total: $paginator->total(),
        );
    }
}
