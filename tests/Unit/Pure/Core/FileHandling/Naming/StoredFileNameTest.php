<?php

namespace Tests\Unit\Pure\Core\FileHandling\Naming;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StoredFileNameTest extends TestCase
{
    #[Test]
    public function it_strips_every_user_controlled_character_from_the_name(): void
    {
        $file = UploadedFile::fake()->create('Mein broken°^„Filename “quoted” 12:30.pdf', 1);

        $name = StoredFileName::forUpload($file);

        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
        $this->assertStringEndsWith('.pdf', $name);
        $this->assertStringNotContainsString(':', $name);
        $this->assertStringNotContainsString(' ', $name);
    }

    #[Test]
    public function it_normalises_a_junk_extension(): void
    {
        $file = UploadedFile::fake()->create('x.PDF„', 1);

        $this->assertStringEndsWith('.pdf', StoredFileName::forUpload($file));
    }

    #[Test]
    public function it_keeps_only_the_last_segment_of_a_multi_part_extension(): void
    {
        $file = UploadedFile::fake()->create('backup.tar.gz', 1);

        $this->assertStringEndsWith('.gz', StoredFileName::forUpload($file));
    }

    #[Test]
    public function it_caps_an_oversized_extension(): void
    {
        $file = UploadedFile::fake()->create('x.' . str_repeat('a', 200), 1);

        $name = StoredFileName::forUpload($file);

        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
        $this->assertLessThanOrEqual(16, strlen(pathinfo($name, PATHINFO_EXTENSION)));
    }

    #[Test]
    #[DataProvider('executableNames')]
    public function it_rewrites_an_executable_extension(string $originalName): void
    {
        $file = UploadedFile::fake()->create($originalName, 1);

        $this->assertStringEndsWith('.bin', StoredFileName::forUpload($file));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function executableNames(): array
    {
        return [
            'php' => ['shell.php'],
            'phtml' => ['shell.phtml'],
            'double extension' => ['report.pdf.exe'],
            'shell script' => ['deploy.sh'],
        ];
    }

    #[Test]
    public function it_produces_a_valid_name_for_a_file_without_an_extension(): void
    {
        $file = UploadedFile::fake()->create('README', 1);

        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, StoredFileName::forUpload($file));
    }

    #[Test]
    public function it_cannot_be_talked_into_a_path(): void
    {
        $file = UploadedFile::fake()->create('../../evil.pdf', 1);

        $name = StoredFileName::forUpload($file);

        $this->assertStringNotContainsString('/', $name);
        $this->assertStringNotContainsString('..', $name);
        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
    }

    #[Test]
    public function it_never_repeats_a_name_for_the_same_file(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 1);

        $names = [];
        for ($i = 0; $i < 1000; $i++) {
            $names[] = StoredFileName::forUpload($file);
        }

        $this->assertCount(1000, array_unique($names));
    }

    #[Test]
    public function it_names_generated_files(): void
    {
        $name = StoredFileName::forGenerated('pdf');

        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
        $this->assertStringEndsWith('.pdf', $name);
    }

    #[Test]
    public function it_normalises_a_generated_extension_and_ignores_the_seed(): void
    {
        $name = StoredFileName::forGenerated('PDF:', '../../../etc/passwd');

        $this->assertStringEndsWith('.pdf', $name);
        $this->assertMatchesRegularExpression(StoredFileName::PATTERN, $name);
    }

    #[Test]
    public function a_generated_file_without_an_extension_is_a_bare_hash(): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', StoredFileName::forGenerated(''));
    }
}
