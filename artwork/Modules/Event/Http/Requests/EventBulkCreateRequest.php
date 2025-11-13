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
            'events.*.name' => 'nullable|string',
            'events.*.start_time' => 'nullable',
            'events.*.end_time' => 'nullable',
            'events.*.end_day' => 'nullable',
            'events.*.room' => 'array',
            'events.*.room.id' => 'integer|exists:rooms,id',
            'events.*.type' => 'array',
            'events.*.type.id' => 'integer|exists:event_types,id',
        ];
    }
}
