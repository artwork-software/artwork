<?php

namespace Artwork\Modules\ExternalAccess\DTOs;

use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;

final class SelfEditSection
{
    /**
     * @param list<SelfEditField> $fields
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly SelfEditSectionMode $mode,
        public readonly string $targetType,
        public readonly int $targetId,
        public readonly array $fields,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'mode' => $this->mode->value,
            'fields' => array_map(static fn (SelfEditField $f) => $f->toArray(), $this->fields),
        ];
    }
}
