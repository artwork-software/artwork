<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Erzeugt die OpenAPI-Spezifikation im Projekt-Root.
 *
 * openapi.json ist das versionierte Contract-Artefakt (Konzept 0.4 §1): Es liegt im Repo, die CI
 * exportiert frisch und vergleicht — Abweichung heisst Contract-Drift und macht den Build rot.
 * artwork-ai friert pro unterstuetzter artwork-Version eine Kopie davon ein.
 *
 * openapi.yaml entsteht daneben als lesbare Fassung und bleibt unversioniert.
 */
class ExportOpenApiCommand extends Command
{
    protected $signature = 'artwork:export-openapi';

    protected $description = 'Generates openapi.yaml from the /api/v1 routes';

    public function handle(): int
    {
        $jsonPath = base_path('openapi.json');
        $yamlPath = base_path('openapi.yaml');

        $this->call('scramble:export', ['--path' => $jsonPath]);

        if (!file_exists($jsonPath)) {
            $this->error('scramble:export did not produce a document.');

            return self::FAILURE;
        }

        $document = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        file_put_contents($yamlPath, Yaml::dump($document, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));

        // Deterministisch schreiben: Ohne feste Formatierung erzeugt jeder Lauf einen Diff und der
        // CI-Vergleich waere dauerhaft rot.
        file_put_contents(
            $jsonPath,
            json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );

        $this->info('openapi.json and openapi.yaml updated.');

        return self::SUCCESS;
    }
}
