<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Services\SeriesEventsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeriesEventsServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(SeriesEventsService::class);

        $this->assertInstanceOf(SeriesEventsService::class, $service);
    }
}
