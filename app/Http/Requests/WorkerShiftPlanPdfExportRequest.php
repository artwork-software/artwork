<?php

namespace App\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerShiftPlanPdfExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::VIEW_SHIFT_PLAN->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $start = Carbon::createFromFormat('Y-m-d', (string) $this->input('start'));
                    $end = Carbon::createFromFormat('Y-m-d', (string) $value);

                    if ($start !== false && $end !== false && $start->diffInDays($end) > 30) {
                        $fail("The {$attribute} range must not exceed 31 days.");
                    }
                },
            ],
            'worker_types' => ['required', 'array', 'min:1'],
            'worker_types.*' => [
                'string',
                Rule::in(['user', 'freelancer', 'service_provider']),
            ],
            'craft_ids' => ['sometimes', 'nullable', 'array'],
            'craft_ids.*' => ['integer', Rule::exists('crafts', 'id')],
            'include_empty_workers' => ['required', 'boolean'],
            'show_shifts' => ['required', 'boolean'],
            'show_individual_times' => ['required', 'boolean'],
            'show_day_services' => ['required', 'boolean'],
            'paperSize' => ['required', Rule::in(['a3', 'a4'])],
            'paperOrientation' => ['required', Rule::in(['landscape', 'portrait'])],
            'dpi' => ['required', 'integer', 'between:72,300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'worker_types' => $this->input('worker_types', ['user', 'freelancer', 'service_provider']),
            'include_empty_workers' => $this->boolean('include_empty_workers'),
            'show_shifts' => $this->has('show_shifts') ? $this->boolean('show_shifts') : true,
            'show_individual_times' => $this->has('show_individual_times')
                ? $this->boolean('show_individual_times')
                : true,
            'show_day_services' => $this->has('show_day_services') ? $this->boolean('show_day_services') : true,
            'paperSize' => $this->input('paperSize', 'a3'),
            'paperOrientation' => $this->input('paperOrientation', 'landscape'),
            'dpi' => $this->integer('dpi', 96),
        ]);
    }
}
