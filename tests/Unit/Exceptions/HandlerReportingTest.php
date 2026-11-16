<?php

namespace Tests\Unit\Exceptions;

use Artwork\Modules\Crm\Exceptions\CrmSettingLockedException;
use Artwork\Modules\Ticketing\Exceptions\TicketingLockedException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class HandlerReportingTest extends TestCase
{
    #[Test]
    public function intended_lock_messages_are_not_reported_to_sentry(): void
    {
        // Sentry HARZTHEATER-17: die Ticketing-Sperre ist eine Meldung an die Person, kein Fehler
        $handler = $this->app->make(ExceptionHandler::class);

        self::assertFalse($handler->shouldReport(new TicketingLockedException('im Verkauf')));
        self::assertFalse($handler->shouldReport(new CrmSettingLockedException('Systemeintrag')));
    }

    #[Test]
    public function real_errors_are_still_reported(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        self::assertTrue($handler->shouldReport(new RuntimeException('kaputt')));
    }
}
