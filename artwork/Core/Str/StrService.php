<?php

namespace Artwork\Core\Str;

use Illuminate\Support\Str;

class StrService
{
    public function random(int $length): string
    {
        return Str::random($length);
    }

    public function createToken(int $length = 128): string
    {
        return $this->random($length);
    }
}
