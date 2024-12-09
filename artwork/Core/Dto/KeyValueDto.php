<?php

namespace Artwork\Core\Dto;

class KeyValueDto
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value
    ) {
    }
}