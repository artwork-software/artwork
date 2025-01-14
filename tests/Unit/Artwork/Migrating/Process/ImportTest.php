<?php

namespace Tests\Unit\Artwork\Migrating\Process;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\Contracts\Importer;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\EventTypeImportModel;
use Artwork\Migrating\Models\ProjectGroupImportModel;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Migrating\Models\RoomImportModel;
use Artwork\Migrating\Process\Import;
use Illuminate\Bus\Dispatcher;
use PHPUnit\Framework\TestCase;
use Throwable;

class ImportTest extends TestCase
{
    protected Import $import;
    protected Importer $importer;
    protected Dispatcher $dispatcher;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importer = $this->createMock(Importer::class);
        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->import = new Import($this->importer);
    }

    public function testHandlesProjectsWithinImportThresholds(): void
    {
        $this->importer->method('getDataAggregator')->willReturn(
            new class implements DataAggregator {
                /**
                 * @return array<int, ProjectImportModel>
                 */
                public function findProjects(): array
                {
                    return [new ProjectImportModel('lel', 'lard', 'a', 'b', now(), now())];
                }

                /**
                 * @return array<int, ProjectGroupImportModel>
                 */
                public function findProjectGroups(): array
                {
                    return [new ProjectGroupImportModel('lel', 'lard', 'a', 'b', now())];
                }

                //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
                public function findProjectGroup(string $identifier): ?ProjectGroupImportModel
                {
                    return null;
                }

                //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
                public function findRoom(string $identifier): ?RoomImportModel
                {
                    return null;
                }

                //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
                public function findEventType(string $identifier): ?EventTypeImportModel
                {
                    return null;
                }

                //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
                public function findEvents(?string $projectIdentifier): array
                {
                    return [];
                }
            }
        );
        $this->importer->method('getConfig')->willReturn(
            new ImportConfig()
        );

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->import->handle($this->dispatcher);
    }
}
