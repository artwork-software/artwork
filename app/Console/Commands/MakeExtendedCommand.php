<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakeExtendedCommand extends Command
{
    protected $signature = 'make:extended 
                            {name : The base name of the module} 
                            {--m : Create a migration for the model} 
                            {--c : Create a controller} 
                            {--r : Create a resource controller} 
                            {--s : Create the BookSteps structure and link the Service in the Controller}';

    protected $description = 'Create a full module with Model, Controller, Migration, Repository, and Service';

    public function handle(): void
    {
        $name = $this->argument('name');
        $createMigration = $this->option('m');
        $createController = $this->option('c');
        $resourceController = $this->option('r');
        $createArtwork = $this->option('s');

        // Erstellen des Models und der Migration
        $this->createModel($name, $createMigration);

        // Erstellen des Controllers
        if ($createController || $resourceController) {
            $this->createController($name, $resourceController, $createArtwork);
        }

        // Erstellen der StageFlow-Struktur
        if ($createArtwork) {
            $this->createArtworkStructure($name);
        }

        $this->info('Extended make command executed successfully.');
    }

    protected function createModel($name, $createMigration): void
    {
        $options = ['name' => $name];

        if ($createMigration) {
            $options['--migration'] = true;
        }

        Artisan::call('make:model', $options);
        $this->info("Model $name created" . ($createMigration ? " with migration." : "."));
    }

    protected function createController($name, $resourceController, $linkService): void
    {
        $controllerName = "{$name}Controller";
        $options = ['name' => $controllerName];

        if ($resourceController) {
            $options['--resource'] = true;
        }

        Artisan::call('make:controller', $options);
        $this->info("Controller $controllerName created" . ($resourceController ? " as a resource controller." : "."));

        if ($linkService) {
            $this->linkServiceInController($name, $controllerName);
        }
    }

    protected function createArtworkStructure($name): void
    {
        $basePath = base_path('artwork/Modules/' . $name);
        $modelPath = $basePath . '/Models';
        $repositoryPath = $basePath . '/Repositories';
        $servicePath = $basePath . '/Services';

        // Ordnerstruktur erstellen
        File::makeDirectory($modelPath, 0755, true, true);
        File::makeDirectory($repositoryPath, 0755, true, true);
        File::makeDirectory($servicePath, 0755, true, true);

        // Model verschieben
        $modelClass = $name;
        $modelFilePath = app_path("/$modelClass.php");
        if (File::exists($modelFilePath)) {
            File::move($modelFilePath, "$modelPath/$modelClass.php");
            $this->updateNamespace("$modelPath/$modelClass.php", "Artwork\\Modules\\$name\\Models");
        }

        // Repository erstellen
        $repositoryClass = $name . 'Repository';
        $this->createFileFromStub('Repository', "$repositoryPath/$repositoryClass.php", [
            '{{namespace}}' => "Artwork\\Modules\\$name\\Repositories",
            '{{class}}' => $repositoryClass,
        ]);

        // Service erstellen
        $serviceClass = $name . 'Service';
        $this->createFileFromStub('Service', "$servicePath/$serviceClass.php", [
            '{{namespace}}' => "Artwork\\Modules\\$name\\Services",
            '{{class}}' => $serviceClass,
            '{{repository}}' => $repositoryClass,
            '{{repositoryVariable}}' => lcfirst($repositoryClass),
            '{{repositoryNamespace}}' => "Artwork\\Modules\\$name\\Repositories\\$repositoryClass",
        ]);

        $this->info("Artwork structure created for $name.");
    }

    protected function linkServiceInController($name, $controllerName): void
    {
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        if (!File::exists($controllerPath)) {
            $this->error("Controller $controllerName does not exist.");
            return;
        }

        $serviceClass = "{$name}Service";
        $serviceNamespace = "Artwork\\Modules\\$name\\Services\\$serviceClass";

        // Dateiinhalt lesen
        $content = File::get($controllerPath);

        // Namespace für den Service hinzufügen, falls nicht vorhanden
        if (strpos($content, "use $serviceNamespace;") === false) {
            $content = preg_replace('/namespace App\\\Http\\\Controllers;/', "namespace App\\Http\\Controllers;\n\nuse $serviceNamespace;", $content, 1);
        }

        // Konstruktor mit private readonly hinzufügen
        $constructor = <<<EOD
            public function __construct(private readonly $serviceClass $$serviceClass)
            {
            }
        EOD;

        // Konstruktor in die Klasse einfügen
        $content = preg_replace('/\{/', "{\n$constructor", $content, 1);

        // Datei speichern
        File::put($controllerPath, $content);

        $this->info("Service $serviceClass linked in $controllerName with private readonly.");
    }

    protected function createFileFromStub($type, $path, $replacements): void
    {
        $stub = File::get(resource_path("stubs/$type.stub"));

        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        File::put($path, $stub);
    }

    protected function updateNamespace($filePath, $namespace): void
    {
        $content = File::get($filePath);
        $content = preg_replace('/namespace\s+[^\s;]+;/', "namespace $namespace;", $content);
        File::put($filePath, $content);
    }
}