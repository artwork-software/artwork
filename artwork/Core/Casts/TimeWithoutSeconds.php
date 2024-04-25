<?php

namespace Artwork\Core\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TimeWithoutSeconds implements CastsAttributes
{
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        return Carbon::parse($value)->format('H:i');
    }

    public function set($model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
