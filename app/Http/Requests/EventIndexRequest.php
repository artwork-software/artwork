<?php

namespace App\Http\Requests;

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
            'roomIds' => ['nullable', 'array'],
            'roomIds.?' => ['nullable', 'exists:rooms,id'],
            'areaIds' => ['nullable', 'array'],
            'areaIds.?' => ['exists:areas,id'],
            'eventTypeIds' => ['nullable', 'array'],
            'eventTypeIds.?' => ['exists:event_types,id'],
            'roomAttributeIds' => ['nullable', 'array'],
            'roomAttributeIds.?' => ['exists:room_attributes,id'],
            'isLoud' => ['nullable', 'boolean'],
            'hasAudience' => ['nullable', 'boolean'],
        ];
    }
}
