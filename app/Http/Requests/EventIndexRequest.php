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

            'filters' => ['sometimes', 'array'],
            'filters.roomIds' => ['sometimes', 'array'],
            'filters.roomIds.?' => ['sometimes', 'exists:rooms,id'],
            'filters.areaIds' => ['sometimes', 'array'],
            'filters.areaIds.?' => ['exists:areas,id'],
            'filters.eventTypeIds' => ['sometimes', 'array'],
            'filters.eventTypeIds.?' => ['exists:event_types,id'],
            'filters.roomAttributeIds' => ['sometimes', 'array'],
            'filters.roomAttributeIds.?' => ['exists:room_attributes,id'],
            'filters.isLoud' => ['sometimes', 'boolean'],
            'filters.hasAudience' => ['sometimes', 'boolean'],
            'filters.adjoiningHasAudience' => ['sometimes', 'boolean'],
            'filters.adjoiningIsLoud' => ['sometimes', 'boolean'],
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
