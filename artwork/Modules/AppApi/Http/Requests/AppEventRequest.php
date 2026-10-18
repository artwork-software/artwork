<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppEventRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'all_day' => ['required', 'boolean'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'event_type_id' => ['required', 'integer', 'exists:event_types,id'],
        ];
    }
}
