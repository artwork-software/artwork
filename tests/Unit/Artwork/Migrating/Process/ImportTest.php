<?php

namespace Tests\Unit\Artwork\Migrating\Process;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\Contracts\Importer;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Jobs\ImportProject;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Migrating\Process\Import;
use Illuminate\Bus\Dispatcher;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Artwork\Migrating\Models\ProjectGroupImportModel;
use Artwork\Migrating\Models\RoomImportModel;
use Artwork\Migrating\Models\EventTypeImportModel;

class ImportTest extends TestCase
{
    protected Import $import;
    protected MockInterface|Importer $importer;
    protected MockInterface|Dispatcher $dispatcher;

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
                public function findProjects(): array
                {
                    return [new ProjectImportModel('lel', 'lard', 'a', 'b', now(), now())];
                }

                public function findProjectGroup(string $identifier): ?ProjectGroupImportModel
                {
                    return null;
                }

                public function findRoom(string $identifier): ?RoomImportModel
                {
                    return null;
                }

                public function findEventType(string $identifier): ?EventTypeImportModel
                {
                    return null;
                }

                public function findEvents(?string $projectIdentifier): array
                {
                    return [];
                }

            }
        );
        $this->importer->method('getConfig')->willReturn(
            new ImportConfig()
        );

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ImportProject::class));

        $this->import->handle($this->dispatcher);
    }
}
