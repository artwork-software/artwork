<?php

namespace Artwork\Migrating\Process;

use Artwork\Migrating\Contracts\Importer;
use Artwork\Migrating\Jobs\ImportProject;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class Import
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(protected readonly Importer $importer)
    {
    }

    public function handle(Dispatcher $dispatcher): void
    {
        $lowerDateImportThreshold = $this->importer->getConfig()->lowerDateImportThreshold();
        $upperDateImportThreshold = $this->importer->getConfig()->upperDateImportThreshold();

        foreach ($this->importer->getDataAggregator()->findProjects() as $project) {
            if (
                ($lowerDateImportThreshold && $project->start <= $lowerDateImportThreshold) ||
                ($upperDateImportThreshold && $project->end >= $upperDateImportThreshold)
            ) {
                continue;
            }
            $dispatcher->dispatch(
                new ImportProject(
                    $this->importer->getConfig(),
                    $this->importer->getDataAggregator(),
                    $project
                )
            );
        }
    }
}
