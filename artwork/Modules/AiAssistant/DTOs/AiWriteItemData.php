<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Objekt, das angelegt, geaendert oder geloescht wuerde (core-write-endpoints-dry-run).
 */
class AiWriteItemData extends Data
{
    /** @param AiWriteConflictData[] $conflicts */
    public function __construct(
        public string $action,
        public ?int $id,
        public ?string $label,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $room,
        public array $conflicts,
    ) {
    }
}
