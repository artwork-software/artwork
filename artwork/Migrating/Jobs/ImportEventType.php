<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Models\EventTypeImportModel;
use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportEventType
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(private readonly EventTypeImportModel $eventTypeImportModel)
    {
    }

    public function handle(): void
    {
        $eventType = new EventType();
        $eventType->name = $this->eventTypeImportModel->name;
        $eventType->hex_code = '#ff00ff';
        $eventType->project_mandatory = false;
        $eventType->save();
    }
}
