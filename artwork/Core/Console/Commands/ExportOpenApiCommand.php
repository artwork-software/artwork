<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Erzeugt openapi.yaml on demand im Projekt-Root. Die Datei ist nicht versioniert — wer die
 * Spezifikation braucht (Partner, Postman, Client-Generator), erzeugt sie mit diesem Command.
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
        unlink($jsonPath);

        $this->info('openapi.yaml updated.');

        return self::SUCCESS;
    }
}
