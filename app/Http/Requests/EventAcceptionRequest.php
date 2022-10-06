<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventAcceptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // todo
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'accepted' => ['required', 'boolean'],
        ];
    }
}
