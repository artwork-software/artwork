<?php

namespace Artwork\Modules\Timeline\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimelinesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timelines' => 'required|array',
            'timelines.*.start_date' => 'required|string',
            'timelines.*.end_date' => 'required|string',
            'timelines.*.start' => 'required|string',
            'timelines.*.end' => 'required|string',
            'timelines.*.description' => 'string|nullable',
        ];
    }
}
