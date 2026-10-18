<?php

namespace Tests\Feature\Factories;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Jedes Model mit HasFactory muss sich per Model::factory()->create() anlegen lassen. Leere
 * definition()-Methoden, Factories im falschen Namespace oder fehlende Pflichtspalten fielen
 * sonst erst auf, wenn ein Test die Factory zum ersten Mal brauchte.
 */
final class ModelFactorySmokeTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: class-string<Model>}>
     */
    public static function factoryModels(): array
    {
        $root = dirname(__DIR__, 3);
        $models = [];
        $directories = array_merge(
            glob($root . '/artwork/Modules/*/Models', GLOB_ONLYDIR) ?: [],
            glob($root . '/app/Models', GLOB_ONLYDIR) ?: []
        );
        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $source = (string) file_get_contents($file->getPathname());
                if (!str_contains($source, 'use HasFactory')) {
                    continue;
                }
                if (!preg_match('/^namespace\s+([^;]+);/m', $source, $namespace)) {
                    continue;
                }
                if (!preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $source, $class)) {
                    continue;
                }
                if (str_contains($class[0], 'abstract')) {
                    continue;
                }
                $fqcn = $namespace[1] . '\\' . $class[1];
                $models[$fqcn] = [$fqcn];
            }
        }
        ksort($models);

        return $models;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('factoryModels')]
    public function the_factory_creates_a_persisted_model(string $modelClass): void
    {
        $this->assertContains(HasFactory::class, class_uses_recursive($modelClass));

        $model = $modelClass::factory()->create();

        $this->assertTrue($model->exists, $modelClass . ' wurde nicht gespeichert');
    }
}
