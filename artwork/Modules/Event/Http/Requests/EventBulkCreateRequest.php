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
            'events' => 'required|array|min:1|max:500',
            'events.*.name' => 'nullable|string',
            'events.*.day' => 'nullable|string',
            'events.*.start_time' => 'nullable',
            'events.*.end_time' => 'nullable',
            'events.*.end_day' => 'nullable',
            'events.*.room' => 'required|array',
            'events.*.room.id' => 'required|integer|exists:rooms,id',
            'events.*.type' => 'required|array',
            'events.*.type.id' => 'required|integer|exists:event_types,id',
            'events.*.is_planning' => 'sometimes|boolean',
        ];
    }
}
