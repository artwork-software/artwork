<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class EventIndexRequest extends FormRequest
{
    public function rules()
    {
        return [
            'projectId' => ['nullable', 'exists:projects,id'],
            'roomId' => ['nullable', 'exists:rooms,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],

            'calendarFilters' => ['sometimes', 'array'],
            'calendarFilters.roomIds' => ['sometimes', 'array'],
            'calendarFilters.roomIds.?' => ['sometimes', 'exists:rooms,id'],
            'calendarFilters.areaIds' => ['sometimes', 'array'],
            'calendarFilters.areaIds.?' => ['exists:areas,id'],
            'calendarFilters.eventTypeIds' => ['sometimes', 'array'],
            'calendarFilters.eventTypeIds.?' => ['exists:event_types,id'],
            'calendarFilters.roomAttributeIds' => ['sometimes', 'array'],
            'calendarFilters.roomAttributeIds.?' => ['exists:room_attributes,id'],
            'calendarFilters.isLoud' => ['sometimes', 'boolean'],
            'calendarFilters.hasAudience' => ['sometimes', 'boolean'],
            'calendarFilters.adjoiningHasAudience' => ['sometimes', 'boolean'],
            'calendarFilters.adjoiningIsLoud' => ['sometimes', 'boolean'],
        ];
    }

    public function filters()
    {
        return [
            'start' => Carbon::parse($this->get('start')),
            'end' => Carbon::parse($this->get('end')),
            'roomIds' => $this->input('calendarFilters.roomIds', []),
            'areaIds' => $this->input('calendarFilters.areaIds', []),
            'eventTypeIds' => $this->input('calendarFilters.eventTypeIds', []),
            'roomAttributeIds' => $this->input('calendarFilters.roomAttributeIds', []),
            'isLoud' => $this->input('calendarFilters.isLoud'),
            'hasAudience' => $this->input('calendarFilters.hasAudience'),
            'adjoiningHasAudience' => $this->input('calendarFilters.adjoiningHasAudience'),
            'adjoiningIsLoud' => $this->input('calendarFilters.adjoiningIsLoud'),
        ];
    }
}
