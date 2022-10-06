<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventIndexRequest extends FormRequest
{
    public function rules()
    {
        return [
            'projectId' => ['nullable', 'exists:projects,id'],
            'roomId' => ['nullable', 'exists:room,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ];
    }
}
