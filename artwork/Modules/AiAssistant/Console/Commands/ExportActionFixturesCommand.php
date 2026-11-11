<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Artwork\Modules\AiAssistant\Support\AiActionHashFixtures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Schreibt die Referenz-Fixtures des `args_hash` (contracts/action-contract.md).
 *
 * Erzeugtes Contract-Artefakt, wie `openapi.json`: die Werte stammen aus der Implementierung, nicht
 * aus der Hand. Das Verzeichnis im Repo ist die Quelle fuer den eigenen Test (CI hat den
 * Spec-Workspace nicht); liegt der Workspace lokal gemountet daneben, geht dieselbe Datei auch
 * dorthin, damit die KI-Instanz sie lesen kann.
 */
class ExportActionFixturesCommand extends Command
{
    protected $signature = 'ai:export-action-fixtures';

    protected $description = 'Schreibt die Referenz-Fixtures fuer den args_hash (Action-Contract).';

    public function handle(): int
    {
        $targets = [base_path(AiActionHashFixtures::DIRECTORY)];
        $workspace = base_path('specs/' . AiActionHashFixtures::DIRECTORY);

        // Der Spec-Workspace haengt im ddev-Container schreibgeschuetzt drin; dorthin kopiert der
        // Mensch bzw. der Host-Lauf. Fehlt er oder ist er nicht beschreibbar, bleibt es beim Repo.
        if (File::isDirectory(dirname($workspace, 2)) && File::isWritable(dirname($workspace, 2))) {
            $targets[] = $workspace;
        }

        foreach ($targets as $directory) {
            File::ensureDirectoryExists($directory);

            foreach (AiActionHashFixtures::withHashes() as $name => $fixture) {
                File::put(
                    $directory . '/' . $name . '.json',
                    json_encode(
                        $fixture,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    ) . PHP_EOL
                );
            }

            $this->info(sprintf('%d Fixture(s) geschrieben: %s', count(AiActionHashFixtures::cases()), $directory));
        }

        return self::SUCCESS;
    }
}
