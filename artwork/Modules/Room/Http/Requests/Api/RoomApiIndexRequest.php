<?php

namespace Artwork\Modules\Room\Http\Requests\Api;

use Artwork\Core\Api\Requests\MachineApiRequest;
use Artwork\Modules\Event\Http\Requests\Api\EventApiIndexRequest;

/**
 * Parameter von GET /api/v1/rooms.
 */
class RoomApiIndexRequest extends MachineApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->explodeCommaSeparated(['areas']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'areas' => ['sometimes', 'array', 'max:' . EventApiIndexRequest::MAX_FILTER_IDS],
            'areas.*' => $this->existingIdRules('areas', 'areas', EventApiIndexRequest::MAX_FILTER_IDS),
        ];
    }

    /**
     * @return list<int>
     */
    public function areaIds(): array
    {
        return array_map('intval', $this->validated('areas', []));
    }
}
