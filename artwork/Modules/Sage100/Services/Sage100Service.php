<?php

namespace Artwork\Modules\Sage100\Services;

use App\Sage100\Sage100;

class Sage100Service
{
    public function __construct()
    {
    }


    public function getData(int $count)
    {
        return app(Sage100::class)->getData([
            "startIndex" => 0,
            "count" => $count
        ]);
    }
}
