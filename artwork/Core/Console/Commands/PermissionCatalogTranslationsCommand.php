<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Illuminate\Console\Command;

/**
 * Prüft, ob jeder Übersetzungsschlüssel des Rechte-Katalogs in lang/de.json und lang/en.json vorhanden ist.
 * Mit --merge werden die Katalog-Übersetzungen (Catalog/translations/*.de.json) in lang/de.json und
 * lang/en.json (Schlüssel = Wert) eingetragen; vorhandene Einträge werden nicht überschrieben.
 */
class PermissionCatalogTranslationsCommand extends Command
{
    protected $signature = 'artwork:permissions:check-translations {--merge : Katalog-Übersetzungen in lang/*.json eintragen}';
    protected $description = 'Prüft (und ergänzt) die Übersetzungen des Rechte-Katalogs';

    public function handle(PermissionCatalog $catalog): int
    {
        $dePath = lang_path('de.json');
        $enPath = lang_path('en.json');
        $de = json_decode((string) file_get_contents($dePath), true, 512, JSON_THROW_ON_ERROR);
        $en = json_decode((string) file_get_contents($enPath), true, 512, JSON_THROW_ON_ERROR);

        if ($this->option('merge')) {
            $added = 0;
            foreach (glob(base_path('artwork/Modules/Permission/Catalog/translations/*.de.json')) ?: [] as $file) {
                $translations = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
                foreach ($translations as $key => $german) {
                    if (!array_key_exists($key, $de)) {
                        $de[$key] = $german;
                        $added++;
                    }
                    if (!array_key_exists($key, $en)) {
                        $en[$key] = $key;
                    }
                }
            }
            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            file_put_contents($dePath, json_encode($de, $flags) . "\n");
            file_put_contents($enPath, json_encode($en, $flags) . "\n");
            $this->info("{$added} Übersetzungen ergänzt.");
        }

        $missingDe = [];
        $missingEn = [];
        foreach ($catalog->translationKeys() as $key) {
            if (!array_key_exists($key, $de)) {
                $missingDe[] = $key;
            }
            if (!array_key_exists($key, $en)) {
                $missingEn[] = $key;
            }
        }

        if ($missingDe === [] && $missingEn === []) {
            $this->info('Alle ' . count($catalog->translationKeys()) . ' Katalog-Schlüssel sind übersetzt.');

            return self::SUCCESS;
        }

        foreach ($missingDe as $key) {
            $this->warn("de.json fehlt: {$key}");
        }
        foreach ($missingEn as $key) {
            $this->warn("en.json fehlt: {$key}");
        }

        return self::FAILURE;
    }
}
