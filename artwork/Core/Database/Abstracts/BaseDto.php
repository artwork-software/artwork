<?php

namespace Artwork\Core\Database\Abstracts;

use Illuminate\Support\Str;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;

abstract class BaseDto implements JsonSerializable, Arrayable
{
    /**
     * To pass the object as response argument which automatically is encoded to json then
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns array containing each property in snake_case, DTO should contain camelCase properties
     * if custom keys are required just overwrite the function
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $property => $value) {
            $result[Str::snake($property)] = $value;
        }

        return $result;
    }
}
