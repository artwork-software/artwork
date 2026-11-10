<?php

namespace Tests\Unit\Core;

use Artwork\Core\Version\InstalledVersion;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InstalledVersionTest extends TestCase
{
    #[Test]
    public function readsTheVersionFromTheFileAndTrimsWhitespace(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'artwork-version');
        file_put_contents($path, "  1.8.1\n");

        try {
            $this->assertSame('1.8.1', InstalledVersion::fromFile($path));
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function missingOrEmptyFileYieldsNull(): void
    {
        $this->assertNull(InstalledVersion::fromFile(sys_get_temp_dir() . '/does-not-exist-' . uniqid()));
        $this->assertNull(InstalledVersion::normalize(''));
        $this->assertNull(InstalledVersion::normalize("\n"));
    }

    #[Test]
    public function acceptsChangelogStyleVersionsAndSuffixes(): void
    {
        $this->assertSame('1.7', InstalledVersion::normalize('1.7'));
        $this->assertSame('1.8.1', InstalledVersion::normalize('v1.8.1'));
        $this->assertSame('1.9.0-dev', InstalledVersion::normalize('1.9.0-dev'));
        $this->assertSame('1.8.1+hotfix.2', InstalledVersion::normalize('1.8.1+hotfix.2'));
    }

    #[Test]
    public function rejectsContentThatIsNoVersionNumber(): void
    {
        $this->assertNull(InstalledVersion::normalize('main'));
        $this->assertNull(InstalledVersion::normalize('1'));
        $this->assertNull(InstalledVersion::normalize('1.8.1 <script>'));
        $this->assertNull(InstalledVersion::normalize("1.8.1\n1.8.2"));
    }

    #[Test]
    public function theVersionFileOfTheRepositoryIsValid(): void
    {
        // Schutz für den Release-Schritt: eine kaputte VERSION-Datei würde in den
        // Tool-Einstellungen nur „unbekannt“ anzeigen.
        $this->assertNotNull(InstalledVersion::fromFile(dirname(__DIR__, 3) . '/VERSION'));
    }
}
