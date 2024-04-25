<?php

namespace Artwork\Modules\Event\Http\Requests;

use Dive\DryRequests\DryRunnable;
use Illuminate\Foundation\Http\FormRequest;

class EventStoreOrUpdateRequest extends FormRequest
{
    use DryRunnable;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes','nullable', 'string'],
            'eventName' => ['required_if:eventNameMandatory,true','nullable','string'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'roomId' => ['sometimes', 'nullable', 'exists:rooms,id'],
            'declinedRoomId' => ['sometimes','nullable','exists:rooms,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'audience' => ['sometimes', 'nullable', 'boolean'],
            'isLoud' => ['sometimes', 'nullable', 'boolean'],
            'occupancyOption' => ['sometimes','nullable','boolean'],
            'projectIdMandatory' => 'required|boolean',
            'creatingProject' => 'required|boolean',
            'eventNameMandatory' => 'required|boolean',
            'projectId' => ['required_if:projectIdMandatory,true', 'nullable', 'exists:projects,id'],
            'projectName' => ['required_unless:creatingProject,false', 'nullable', 'string'],
            'eventTypeId' => ['required', 'exists:event_types,id'],
            'adminComment' => ['sometimes','nullable','string'],
            'optionString' => ['sometimes','nullable','string'],
            'allDay' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
