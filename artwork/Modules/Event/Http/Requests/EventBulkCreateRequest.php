<?php

namespace Artwork\Modules\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventBulkCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => 'required|array',
            'events.*.name' => 'string',
            'events.*.start_time' => 'nullable|date',
            'events.*.end_time' => 'nullable|date',
            'events.*.room' => 'array',
            'events.*.room.id' => 'integer|exists:rooms,id',
            'events.*.type' => 'array',
            'events.*.type.id' => 'integer|exists:event_types,id',
        ];
    }
}
