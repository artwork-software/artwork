<?php

namespace App\Http\Requests;

use Dive\DryRequests\DryRunnable;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateEvent extends FormRequest
{
    use DryRunnable;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'start_time' => 'required|before:end_time',
            'end_time' => 'required',
            'name' => 'string|nullable',
            'description' => 'string|nullable',
        ];
    }
}
