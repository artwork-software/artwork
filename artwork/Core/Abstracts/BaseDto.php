<?php

namespace Artwork\Core\Abstracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class BaseDto implements JsonSerializable, Arrayable
{
    /**
     * To pass the object as response argument which automatically is encoded to json then
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        //late static binding means: the last overwriting method is used. If ClassA derives from BaseDto
        //and Class B derives from Class A, Class B's toArray implementation is used if it exists
        return static::toArray();
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
