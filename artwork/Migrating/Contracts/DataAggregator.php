<?php

namespace Artwork\Migrating\Contracts;

use Artwork\Migrating\Models\EventImportModel;
use Artwork\Migrating\Models\EventTypeImportModel;
use Artwork\Migrating\Models\ProjectGroupImportModel;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Migrating\Models\RoomImportModel;

interface DataAggregator
{
    /** @return ProjectImportModel[] */
    public function findProjects(): array;

    public function findProjectGroup(string $identifier): ?ProjectGroupImportModel;

    public function findRoom(string $identifier): ?RoomImportModel;

    public function findEventType(string $identifier): ?EventTypeImportModel;

    /** @return EventImportModel[] */
    public function findEvents(?string $projectIdentifier): array;
}
