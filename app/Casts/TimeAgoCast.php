<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TimeAgoCast implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes): mixed
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
