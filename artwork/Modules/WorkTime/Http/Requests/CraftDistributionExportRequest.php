<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CraftDistributionExportRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                // Zeitraum begrenzen: alle ShiftWorker-Zeilen des Zeitraums werden in den
                // Speicher geladen — ohne Cap ist der Export per URL auf OOM/Timeout treibbar
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $start = Carbon::createFromFormat('Y-m-d', (string) $this->input('start_date'));
                    $end = Carbon::createFromFormat('Y-m-d', $value);
                    if ($start !== false && $end !== false && $start->diffInDays($end) > 1100) {
                        $fail('The export range must not exceed 36 months.');
                    }
                },
            ],
            'universal_craft_id' => [
                'required',
                'integer',
                Rule::exists('crafts', 'id')->where('universally_applicable', true),
            ],
            'crafts' => ['sometimes', 'array'],
            'crafts.*' => ['integer', Rule::exists('crafts', 'id')],
        ];
    }
}
