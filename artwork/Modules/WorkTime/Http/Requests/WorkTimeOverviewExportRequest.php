<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Carbon\Carbon;
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
            'end_month' => [
                'required',
                'date_format:Y-m',
                'after_or_equal:start_month',
                // Zeitraum begrenzen: alle ShiftWorker-Zeilen des Zeitraums werden in den
                // Speicher geladen — ohne Cap ist der Export per URL auf OOM/Timeout treibbar
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $start = Carbon::createFromFormat('Y-m-d', $this->input('start_month') . '-01');
                    $end = Carbon::createFromFormat('Y-m-d', $value . '-01');
                    if ($start !== false && $end !== false && $start->diffInMonths($end) >= 36) {
                        $fail('The export range must not exceed 36 months.');
                    }
                },
            ],
            'crafts' => ['sometimes', 'array'],
            'crafts.*' => ['integer', Rule::exists('crafts', 'id')],
        ];
    }
}
