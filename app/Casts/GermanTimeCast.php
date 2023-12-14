<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class GermanTimeCast implements CastsAttributes
{
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed,Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed
    public function get($model, string $key, mixed $value, array $attributes): string
    {
        return Carbon::parse($value)->translatedFormat('D, d.m.Y');
    }

    public function set($model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
