<?php

namespace Tests\Feature\Routing;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Jede Inertia-Seite, die der Server rendert, braucht ihre Vue-Datei. Sonst bleibt der Browser leer,
 * während der Server 200 meldet (so lebten neun tote Seiten wie projects/create unbemerkt weiter).
 * Gast-Seiten des ExternalAccess-Moduls löst app-external.js unter Pages/ExternalAccess auf.
 */
final class InertiaPagesExistTest extends TestCase
{
    #[Test]
    public function every_rendered_inertia_page_has_a_vue_file(): void
    {
        $missing = [];
        foreach (['app', 'artwork'] as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));
            foreach ($files as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $source = (string) file_get_contents($file->getPathname());
                preg_match_all(
                    "/(?:Inertia::render|\binertia|->render)\(\s*'([A-Z][A-Za-z0-9\/_-]+)'/",
                    $source,
                    $matches
                );
                // Das ExternalAccess-Modul rendert Gast-Seiten (Pages/ExternalAccess) und Einstellungsseiten der App
                $pageRoots = str_contains($file->getPathname(), '/Modules/ExternalAccess/')
                    ? ['resources/js/Pages/ExternalAccess/', 'resources/js/Pages/']
                    : ['resources/js/Pages/'];

                foreach ($matches[1] as $page) {
                    $exists = array_filter($pageRoots, static fn (string $root): bool => is_file(base_path($root . $page . '.vue')));
                    if ($exists === []) {
                        $missing[] = $page . ' (' . str_replace(base_path() . '/', '', $file->getPathname()) . ')';
                    }
                }
            }
        }

        self::assertSame([], array_values(array_unique($missing)));
    }
}
