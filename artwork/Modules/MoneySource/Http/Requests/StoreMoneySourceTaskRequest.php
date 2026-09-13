<?php

namespace Artwork\Modules\MoneySource\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMoneySourceTaskRequest extends FormRequest
{
    /**
     * Autorisierung läuft im Controller über MoneySourceTaskPolicy::create mit der
     * geladenen Finanzierungsquelle.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'money_source' => ['required', 'integer', 'exists:money_sources,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        // Das Frontend hängt "Datum Uhrzeit" zusammen; ohne Datum entsteht "null null".
        $deadline = $this->input('deadline');
        if (is_string($deadline) && (trim($deadline) === '' || str_starts_with(trim($deadline), 'null'))) {
            $this->merge(['deadline' => null]);
        }
    }
}
