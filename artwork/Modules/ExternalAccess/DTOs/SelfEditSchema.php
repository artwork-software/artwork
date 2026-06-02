<?php

namespace Artwork\Modules\ExternalAccess\DTOs;

final class SelfEditSchema
{
    /**
     * @param list<SelfEditSection> $sections
     */
    public function __construct(
        public readonly array $sections,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sections' => array_map(static fn (SelfEditSection $s) => $s->toArray(), $this->sections),
        ];
    }
}
