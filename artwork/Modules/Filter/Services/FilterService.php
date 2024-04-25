<?php

namespace Artwork\Modules\Filter\Services;

use Artwork\Modules\Filter\Repositories\FilterRepository;

readonly class FilterService
{
    public function __construct(private FilterRepository $filterRepository)
    {
    }
}
