<?php

namespace Artwork\Migrating;

use Carbon\Carbon;
use Illuminate\Config\Repository;

class ImportConfig extends Repository
{
    public const SHOULD_CREATE_EVENT_TYPE = 'create_event_type';
    public const SHOULD_CREATE_ROOM = 'create_room';
    public const EMPTY_START_DATE_IS_WHOLE_DAY = 'empty_start_date_is_whole_day';
    public const EMPTY_END_DATE_IS_WHOLE_DAY = 'empty_end_date_is_whole_day';
    public const LOWER_DATE_IMPORT_THRESHOLD = 'lower_date_import_threshold';
    public const UPPER_DATE_IMPORT_THRESHOLD = 'upper_date_import_threshold';
    public const SHOULD_IMPORT_PROJECT_GROUPS = 'import_project_groups';

    public function shouldCreateEventType(): bool
    {
        return $this->get(static::SHOULD_CREATE_EVENT_TYPE, false);
    }

    public function shouldImportProjectGroups(): bool
    {
        return $this->get(static::SHOULD_IMPORT_PROJECT_GROUPS, true);
    }

    public function shouldCreateRoom(): bool
    {
        return $this->get(static::SHOULD_CREATE_ROOM, false);
    }

    public function emptyStartTimeIsWholeDay(): bool
    {
        return $this->get(static::EMPTY_START_DATE_IS_WHOLE_DAY, true);
    }

    public function emptyEndTimeIsEndOfDay(): bool
    {
        return $this->get(static::EMPTY_END_DATE_IS_WHOLE_DAY, true);
    }

    public function lowerDateImportThreshold(): ?Carbon
    {
        return $this->get(static::LOWER_DATE_IMPORT_THRESHOLD);
    }

    public function upperDateImportThreshold(): ?Carbon
    {
        return $this->get(static::UPPER_DATE_IMPORT_THRESHOLD);
    }
}
