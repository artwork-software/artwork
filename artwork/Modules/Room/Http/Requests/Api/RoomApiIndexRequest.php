<?php

namespace Artwork\Modules\Room\Http\Requests\Api;

use Artwork\Core\Api\Requests\MachineApiRequest;
use Illuminate\Validation\Rule;

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
            'areas' => ['sometimes', 'array'],
            'areas.*' => ['integer', 'distinct', Rule::exists('areas', 'id')->whereNull('deleted_at')],
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
