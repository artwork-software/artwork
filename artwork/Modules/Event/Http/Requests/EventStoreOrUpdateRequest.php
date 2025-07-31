<?php

namespace Artwork\Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventStoreOrUpdateRequest extends FormRequest
{

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
            'showProjectPeriodInCalendar' => ['sometimes', 'nullable', 'boolean'],
        ];
    }


    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eventName.required_if'       => __('validation.custom.eventName.required_if'),
            'projectId.required_if'       => __('validation.custom.projectId.required_if'),
            'projectName.required_unless' => __('validation.custom.projectName.required_unless'),
            'start.required'              => __('validation.custom.start.required'),
            'start.date'                  => __('validation.custom.start.date'),
            'end.required'                => __('validation.custom.end.required'),
            'end.date'                    => __('validation.custom.end.date'),
            'end.after'                   => __('validation.custom.end.after'),
            'roomId.exists'               => __('validation.custom.roomId.exists'),
            'declinedRoomId.exists'       => __('validation.custom.declinedRoomId.exists'),
            'eventTypeId.required'        => __('validation.custom.eventTypeId.required'),
            'eventTypeId.exists'          => __('validation.custom.eventTypeId.exists'),
            'eventStatusId.exists'        => __('validation.custom.eventStatusId.exists'),
            'projectIdMandatory.required' => __('validation.custom.projectIdMandatory.required'),
            'eventNameMandatory.required' => __('validation.custom.eventNameMandatory.required'),
            'creatingProject.required'    => __('validation.custom.creatingProject.required'),
        ];
    }
}
