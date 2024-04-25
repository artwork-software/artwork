<?php

namespace Artwork\Core\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TimeAgoCast implements CastsAttributes
{
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
    public function get($model, string $key, mixed $value, array $attributes): string
    {
        $now = Carbon::now();
        $time = Carbon::parse($value);
        return $time->diffForHumans($now);
    }

    public function set($model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
