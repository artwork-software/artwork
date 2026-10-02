<?php

namespace Tests\Feature\Lang;

use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Jeder wörtliche __()/trans()/@lang-Schlüssel im Backend muss auf Deutsch und Englisch existieren.
 * Fehlt ein Datei-Schlüssel (z. B. "flash-messages.permission-preset.success.created" statt
 * "...success.create"), sehen Nutzer:innen den rohen Schlüssel als Meldung; fehlt ein Text-Schlüssel
 * in de.json, erscheint der englische Text in der deutschen Oberfläche.
 */
final class BackendTranslationKeysTest extends TestCase
{
    private const SCANNED_DIRECTORIES = ['app', 'artwork', 'resources/views'];

    private const TRANSLATION_CALL = '/(?<![\w>$])(?:__|trans|@lang)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)")\s*[,)]/';

    #[Test]
    public function every_literal_backend_translation_key_exists_in_german_and_english(): void
    {
        $germanJson = json_decode((string) file_get_contents(lang_path('de.json')), true);
        $missing = [];

        foreach ($this->literalKeys() as $key => $file) {
            if ($this->isFileKey($key)) {
                foreach (['de', 'en'] as $locale) {
                    if (!Lang::has($key, $locale, false)) {
                        $missing[] = "$locale: $key ($file)";
                    }
                }
                continue;
            }

            if (!array_key_exists($key, $germanJson)) {
                $missing[] = "de.json: $key ($file)";
            }
        }

        $this->assertSame([], $missing, "Fehlende Übersetzungen:\n" . implode("\n", $missing));
    }

    /**
     * @return array<string, string> Schlüssel => erste Fundstelle
     */
    private function literalKeys(): array
    {
        $keys = [];
        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));
            foreach ($iterator as $file) {
                $path = (string) $file;
                if (!str_ends_with($path, '.php')) {
                    continue;
                }
                preg_match_all(self::TRANSLATION_CALL, (string) file_get_contents($path), $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $key = stripcslashes($match[1] !== '' ? $match[1] : ($match[2] ?? ''));
                    if ($key === '' || str_contains($key, '{{')) {
                        continue;
                    }
                    $keys[$key] ??= str_replace(base_path() . '/', '', $path);
                }
            }
        }

        return $keys;
    }

    /**
     * "datei.schlüssel" mit vorhandener lang/de/datei.php; alles andere ist ein JSON-Text-Schlüssel.
     */
    private function isFileKey(string $key): bool
    {
        if (!preg_match('/^([a-z0-9_-]+)\.[\w.-]+$/i', $key, $match)) {
            return false;
        }

        return file_exists(lang_path('de/' . $match[1] . '.php'));
    }
}
