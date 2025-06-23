<?php

namespace Artwork\Modules\User\Http\Requests;

use Artwork\Modules\User\Http\Requests\StoreUserWorkTimePatternRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserWorkTimePatternRequest extends StoreUserWorkTimePatternRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:user_work_time_patterns,id',
            ...parent::rules()
        ];
    }
}
