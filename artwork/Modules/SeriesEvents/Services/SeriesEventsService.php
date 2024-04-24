<?php

namespace Artwork\Modules\SeriesEvents\Services;

use Artwork\Modules\SeriesEvents\Repositories\SeriesEventsRepository;

readonly class SeriesEventsService
{
    public function __construct(private SeriesEventsRepository $seriesEventsRepository)
    {
    }
}
