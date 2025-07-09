<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkTimeChangeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'request_start_time' => 'required|string|date_format:H:i',
            'request_end_time' => 'required|string|date_format:H:i|after:request_start_time',
            'shift_id' => 'required|exists:shifts,id',
            'craft_id' => 'required|exists:crafts,id',
            'request_comment' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'requested_by' => 'required|exists:users,id',
        ];
    }
}
