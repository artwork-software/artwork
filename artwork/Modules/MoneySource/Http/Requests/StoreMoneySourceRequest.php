<?php

namespace Artwork\Modules\MoneySource\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sicherheits-Audit 21.09.2026 (E, MITTEL): store/update liefen ohne Validierung — group_id und
 * sub_money_source_ids waren beliebig, amount nicht numerisch. Die Schreibrechte auf die verknüpften
 * Quellen prüft der Controller (MoneySourcePolicy::update je Quelle).
 */
class StoreMoneySourceRequest extends FormRequest
{
    /**
     * Autorisierung läuft im Controller über MoneySourcePolicy (create/update).
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'funding_start_date' => ['nullable', 'date'],
            'funding_end_date' => ['nullable', 'date'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'is_group' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', 'exists:money_sources,id'],
            'sub_money_source_ids' => ['nullable', 'array'],
            'sub_money_source_ids.*' => ['integer', 'exists:money_sources,id'],
            'users' => ['nullable', 'array'],
            'users.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'users.*.competent' => ['nullable', 'boolean'],
            'users.*.write_access' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Das Frontend liefert Beträge mit Komma ("1.234,50" ist nicht vorgesehen, nur "1234,50").
        $amount = $this->input('amount');
        if (is_string($amount)) {
            $amount = trim(str_replace(',', '.', $amount));
            $this->merge(['amount' => $amount === '' ? null : $amount]);
        }
    }
}
