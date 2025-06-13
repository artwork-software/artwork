<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Repositories\SeriesEventsRepository;

readonly class SeriesEventsService
{
    public function __construct(private SeriesEventsRepository $seriesEventsRepository)
    {
    }
}
