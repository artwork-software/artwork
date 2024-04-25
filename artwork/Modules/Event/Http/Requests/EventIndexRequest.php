<?php

namespace Artwork\Modules\Event\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class EventIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'projectId' => ['nullable', 'exists:projects,id'],
            'roomId' => ['nullable', 'exists:rooms,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],

            'calendarFilters' => ['sometimes', 'json'],
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
            'roomIds' => $this->input('filters.roomIds', []),
            'areaIds' => $this->input('filters.areaIds', []),
            'eventTypeIds' => $this->input('filters.eventTypeIds', []),
            'roomAttributeIds' => $this->input('filters.roomAttributeIds', []),
            'isLoud' => $this->input('filters.isLoud'),
            'hasAudience' => $this->input('filters.hasAudience'),
            'adjoiningHasAudience' => $this->input('filters.adjoiningHasAudience'),
            'adjoiningIsLoud' => $this->input('filters.adjoiningIsLoud'),
        ];
    }
}
