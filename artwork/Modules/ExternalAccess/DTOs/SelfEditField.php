<?php

namespace Artwork\Modules\ExternalAccess\DTOs;

final class SelfEditField
{
    /**
     * @param list<string> $options Auswahlwerte bei inputType "select" (sonst leer)
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $inputType,
        public readonly bool $required,
        public readonly ?string $value,
        public readonly array $options = [],
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
            'inputType' => $this->inputType,
            'required' => $this->required,
            'value' => $this->value,
            'options' => $this->options,
        ];
    }
}
