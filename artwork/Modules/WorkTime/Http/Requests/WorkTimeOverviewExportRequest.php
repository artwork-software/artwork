<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkTimeOverviewExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['required', 'date_format:Y-m', 'after_or_equal:start_month'],
            'crafts' => ['sometimes', 'array'],
            'crafts.*' => ['integer', Rule::exists('crafts', 'id')],
        ];
    }
}
