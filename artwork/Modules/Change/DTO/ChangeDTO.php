<?php

namespace Artwork\Modules\Change\DTO;

use Carbon\CarbonInterface;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\LaravelData\Data;

class ChangeDTO extends Data
{
    /**
     * @param array<int|string, mixed> $properties
     */
    public function __construct(
        public int $id,
        public ?string $logName,
        public ?string $event,
        public ?string $subjectType,
        public ?int $subjectId,
        public ?string $causerType,
        public ?int $causerId,
        public array $properties,
        public CarbonInterface $createdAt,
    ) {
    }

    public static function fromActivity(Activity $activity): self
    {
        $properties = $activity->properties;

        return new self(
            id: (int) $activity->id,
            logName: $activity->log_name,
            event: $activity->event,
            subjectType: $activity->subject_type,
            subjectId: $activity->subject_id !== null ? (int) $activity->subject_id : null,
            causerType: $activity->causer_type,
            causerId: $activity->causer_id !== null ? (int) $activity->causer_id : null,
            properties: is_array($properties) ? $properties : $properties->all(),
            createdAt: $activity->created_at,
        );
    }
}
