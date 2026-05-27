<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiEventTypeTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
