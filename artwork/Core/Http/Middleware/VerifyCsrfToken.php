<?php

namespace Artwork\Core\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = ['*'];
}
