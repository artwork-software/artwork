<?php

namespace Tests\Unit\Pure\Core\FileHandling\Naming;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Guards the rule the hashed-filename work established: a client supplied name
 * may reach the database, never the disk. Every store call has to take its name
 * from StoredFileName.
 */
final class NoRawFilenameOnDiskTest extends TestCase
{
    #[Test]
    public function no_store_call_uses_the_client_filename(): void
    {
        $offenders = [];

        foreach ($this->phpFiles() as $file) {
            $lines = file($file->getPathname()) ?: [];

            foreach ($lines as $number => $line) {
                if (preg_match('/(storeAs|putFileAs|->store\(|storePublicly)[^;]*getClientOriginalName/', $line)) {
                    $offenders[] = $this->relativePath($file) . ':' . ($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "A file is stored under its client supplied name. Use StoredFileName::forUpload() instead:\n"
            . implode("\n", $offenders)
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function phpFiles(): array
    {
        $files = [];

        foreach ([base_path('app'), base_path('artwork')] as $directory) {
            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function relativePath(SplFileInfo $file): string
    {
        return str_replace(base_path() . '/', '', $file->getPathname());
    }
}
