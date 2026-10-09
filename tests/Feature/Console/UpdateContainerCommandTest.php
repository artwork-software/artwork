<?php

namespace Tests\Feature\Console;

use Artwork\Core\Console\Commands\UpdateContainerCommand;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Console\OutputStyle;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Feature\FeatureTestCase;

/**
 * artwork:container-update legte Meilisearch-Indizes unter fest eingetragenen Namen an
 * (serviceproviders, moneysources, inventoryarticles), Scout sucht aber in service_providers,
 * money_sources und inventory_articles — die Suche lief dann auf einen fehlenden Index.
 */
final class UpdateContainerCommandTest extends FeatureTestCase
{
    #[Test]
    public function meilisearch_indexes_are_created_under_the_names_scout_searches_in(): void
    {
        $createdIndexes = $this->useEngineRecordingCreatedIndexes();

        $command = app(UpdateContainerCommand::class);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));
        (new \ReflectionMethod($command, 'syncMeilisearchIndexes'))->invoke($command);

        $expected = array_map(
            fn(string $model): string => (new $model())->searchableAs(),
            UpdateContainerCommand::SEARCHABLE_MODELS
        );
        $this->assertSame($expected, $createdIndexes->names);
        $this->assertContains((new ServiceProvider())->searchableAs(), $createdIndexes->names);
        $this->assertContains((new MoneySource())->searchableAs(), $createdIndexes->names);
        $this->assertContains((new InventoryArticle())->searchableAs(), $createdIndexes->names);
    }

    #[Test]
    public function meilisearch_index_names_include_the_scout_prefix(): void
    {
        config(['scout.prefix' => 'haus_']);
        $createdIndexes = $this->useEngineRecordingCreatedIndexes();

        $command = app(UpdateContainerCommand::class);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));
        (new \ReflectionMethod($command, 'syncMeilisearchIndexes'))->invoke($command);

        $this->assertContains('haus_service_providers', $createdIndexes->names);
        $this->assertContains('haus_money_sources', $createdIndexes->names);
    }

    /**
     * Scout-Engine, die nur die Namen der angelegten Indizes mitschreibt.
     */
    private function useEngineRecordingCreatedIndexes(): object
    {
        $recorder = new class {
            /** @var array<int, string> */
            public array $names = [];
        };

        app(EngineManager::class)->extend('recording', fn() => new class ($recorder) extends NullEngine {
            public function __construct(private readonly object $recorder)
            {
            }

            public function createIndex($name, array $options = []): mixed
            {
                $this->recorder->names[] = $name;

                return [];
            }
        });

        config(['scout.driver' => 'recording']);
        app(EngineManager::class)->forgetDrivers();

        return $recorder;
    }
}
