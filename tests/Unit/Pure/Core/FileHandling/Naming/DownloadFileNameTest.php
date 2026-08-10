<?php

namespace Tests\Unit\Pure\Core\FileHandling\Naming;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DownloadFileNameTest extends TestCase
{
    #[Test]
    public function it_keeps_a_readable_name(): void
    {
        $this->assertSame(
            '10.08.2026-12-30-00_Mein_Projekt_dpi_72.pdf',
            DownloadFileName::sanitize('10.08.2026-12-30-00_Mein_Projekt_dpi_72.pdf')
        );
    }

    #[Test]
    public function it_keeps_umlauts_and_typographic_quotes(): void
    {
        $this->assertSame('Übersicht „Sommer“.pdf', DownloadFileName::sanitize('Übersicht „Sommer“.pdf'));
    }

    #[Test]
    public function it_removes_characters_that_would_break_the_disposition_header(): void
    {
        // Dots are legitimate in a display name; only the separators have to go.
        $this->assertSame('....etcpasswd', DownloadFileName::sanitize('../../etc/passwd'));
        $this->assertSame('100.pdf', DownloadFileName::sanitize('100%.pdf'));
        $this->assertSame('abc.pdf', DownloadFileName::sanitize("a\r\nb\0c.pdf"));
    }

    #[Test]
    public function it_rejects_anything_that_is_not_a_usable_name(): void
    {
        $this->assertNull(DownloadFileName::sanitize(null));
        $this->assertNull(DownloadFileName::sanitize(['x.pdf']));
        $this->assertNull(DownloadFileName::sanitize('   '));
        $this->assertNull(DownloadFileName::sanitize('/'));
        $this->assertNull(DownloadFileName::sanitize('..'));
    }

    #[Test]
    public function it_caps_the_length(): void
    {
        $name = DownloadFileName::sanitize(str_repeat('a', 500) . '.pdf');

        $this->assertNotNull($name);
        $this->assertSame(200, mb_strlen($name));
    }
}
