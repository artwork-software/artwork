<?php

namespace Tests\Unit\Core;

use Artwork\Core\FileHandling\ServerUploadLimit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ServerUploadLimitTest extends TestCase
{
    #[Test]
    public function theSmallerOfBothDirectivesWins(): void
    {
        $this->assertSame(64, ServerUploadLimit::fromIniValues('64M', '128M'));
        $this->assertSame(100, ServerUploadLimit::fromIniValues('2G', '100M'));
    }

    #[Test]
    public function unlimitedDirectivesYieldZeroInsteadOfAnIntOverflow(): void
    {
        // Regressionsschutz: (int) PHP_FLOAT_MAX warf ein E_WARNING, das Laravel in
        // eine ErrorException verwandelte — und HandleInertiaRequests wertet das
        // Limit auf JEDEM Request aus.
        $this->assertSame(0, ServerUploadLimit::fromIniValues('0', '0'));
        $this->assertSame(0, ServerUploadLimit::fromIniValues('-1', '-1'));
        $this->assertSame(0, ServerUploadLimit::fromIniValues(false, ''));
    }

    #[Test]
    public function oneUnlimitedDirectiveFallsBackToTheOther(): void
    {
        $this->assertSame(512, ServerUploadLimit::fromIniValues('-1', '512M'));
        $this->assertSame(8, ServerUploadLimit::fromIniValues('8M', '0'));
    }

    #[Test]
    public function subMegabyteLimitsAreReportedAsOneInsteadOfAFalsyZero(): void
    {
        $this->assertSame(1, ServerUploadLimit::fromIniValues('512K', '512K'));
    }

    #[Test]
    public function plainByteValuesAreSupported(): void
    {
        $this->assertSame(2, ServerUploadLimit::fromIniValues('2097152', '2097152'));
    }
}
