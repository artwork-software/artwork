<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Services\EventCalendarExportBladeTemplateService;
use Artwork\Modules\Event\Services\EventCalendarExportService;
use Artwork\Modules\Event\Services\EventListExportService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Smoke-only tests for Excel/PDF-heavy export services.
 * The full export pipelines depend on IO (file generation, blade rendering, downloads)
 * and are covered indirectly via controller feature tests. Here we only verify
 * the services can be resolved from the container without configuration errors.
 */
final class ExportServiceResolutionTest extends TestCase
{
    #[Test]
    public function event_calendar_export_service_resolves_from_container(): void
    {
        $service = app(EventCalendarExportService::class);

        $this->assertInstanceOf(EventCalendarExportService::class, $service);
    }

    #[Test]
    public function event_list_export_service_resolves_from_container(): void
    {
        $service = app(EventListExportService::class);

        $this->assertInstanceOf(EventListExportService::class, $service);
    }

    #[Test]
    public function event_calendar_export_blade_template_service_resolves_from_container(): void
    {
        $service = app(EventCalendarExportBladeTemplateService::class);

        $this->assertInstanceOf(EventCalendarExportBladeTemplateService::class, $service);
    }
}
