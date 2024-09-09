<?php

namespace Artwork\Modules\UserCalendarSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleUseProjectTimePeriodRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'use_project_time_period' => 'required|boolean',
            'project_id' => Rule::when(
                $this->boolean('use_project_time_period'),
                'exists:projects,id',
                'required'
            )
        ];
    }
}
