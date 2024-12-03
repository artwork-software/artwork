<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateArtworkStructure extends Command
{

    protected $signature = 'make:artwork {name : Der Name des Hauptordners}';
    protected $description = 'Erstellt eine Ordnerstruktur im Namespace Artwork mit Services, Repositories und Models';

    public function handle()
    {
        $name = ucfirst($this->argument('name')); // Name großschreiben
        $basePath = base_path('artwork/Modules/' . $name);

        $directories = [
            $basePath . '/Services',
            $basePath . '/Repositories',
            $basePath . '/Models',
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Erstellt: {$directory}");
            } else {
                $this->info("Existiert bereits: {$directory}");
            }
        }

        // Service-Klasse erstellen
        $this->createClass($basePath . '/Services', "{$name}Service", $this->getServiceTemplate($name));

        // Repository-Klasse erstellen
        $this->createClass($basePath . '/Repositories', "{$name}Repository", $this->getRepositoryTemplate($name));

        return 0;
    }

    /**
     * Erstellt eine Klasse mit einer Vorlage.
     *
     * @param string $directory
     * @param string $fileName
     * @param string $content
     */
    protected function createClass(string $directory, string $fileName, string $content): void
    {
        $filePath = "{$directory}/{$fileName}.php";

        if (!File::exists($filePath)) {
            File::put($filePath, $content);
            $this->info("Klasse erstellt: {$filePath}");
        } else {
            $this->warn("Klasse existiert bereits: {$filePath}");
        }
    }

    /**
     * Gibt die Vorlage für die Service-Klasse zurück.
     *
     * @param string $name
     * @return string
     */
    protected function getServiceTemplate(string $name): string
    {
        return <<<PHP
        <?php

        namespace Artwork\Modules\\{$name}\Services;

        use Artwork\Modules\\{$name}\Repositories\\{$name}Repository;

        readonly class {$name}Service
        {
            public function __construct(
                private readonly {$name}Repository \${$name}Repository
            ) {
            }
        }
        PHP;
    }

    /**
     * Gibt die Vorlage für die Repository-Klasse zurück.
     *
     * @param string $name
     * @return string
     */
    protected function getRepositoryTemplate(string $name): string
    {
        return <<<PHP
        <?php

        namespace Artwork\Modules\\{$name}\Repositories;

        use Artwork\Core\Database\Repository\BaseRepository;

        class {$name}Repository extends BaseRepository
        {
        }
        PHP;
    }
}
