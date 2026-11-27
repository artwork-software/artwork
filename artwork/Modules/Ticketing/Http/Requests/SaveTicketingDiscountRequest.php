<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Ein automatischer Rabatt aus dem Tab "Rabatte & Pakete": Frühbucher, Staffel oder Paket. */
class SaveTicketingDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Berechtigung liegt auf der Routengruppe (manage ticketing).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'kind' => ['required', Rule::in(['percent', 'fixed', 'package'])],
            'value' => 'required|integer|min:1',
            // Staffel ab so vielen verschiedenen Terminen einer Buchung, beim Paket seine Größe.
            'minDates' => 'nullable|integer|min:2|max:50|required_if:kind,package',
            'allEvents' => 'required|boolean',
            'eventIds' => 'present|array|max:200',
            'eventIds.*' => 'uuid',
            'groupIds' => 'present|array|max:50',
            'groupIds.*' => 'uuid',
            'validFrom' => 'nullable|date_format:Y-m-d',
            'validUntil' => 'nullable|date_format:Y-m-d|after_or_equal:validFrom',
            'status' => ['required', Rule::in(['active', 'paused'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('kind') === 'percent' && (int) $this->input('value') > 10000) {
                $validator->errors()->add('value', __('A percent discount cannot exceed 100 %.'));
            }

            $scoped = $this->input('eventIds', []) !== [] || $this->input('groupIds', []) !== [];

            if (!$this->boolean('allEvents') && !$scoped) {
                $validator->errors()->add('eventIds', __('Pick at least one production or group.'));
            }
        });
    }
}
