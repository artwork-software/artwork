<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventStoreOrUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'roomId' => ['sometimes', 'nullable', 'exists:rooms,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'audience' => ['sometimes', 'nullable', 'boolean'],
            'isLoud' => ['sometimes', 'nullable', 'boolean'],
            'projectId' => ['sometimes', 'nullable', 'exists:projects,id'],
            'projectName' => ['sometimes', 'nullable', 'string'],
            'eventTypeId' => ['required', 'exists:event_types,id'],
        ];
    }
}
