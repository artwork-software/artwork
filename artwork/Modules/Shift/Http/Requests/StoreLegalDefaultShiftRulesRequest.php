<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Shift\Support\ShiftRuleSets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Regelsatz anlegen: Auswahl des Satzes (ShiftRuleSets, ohne Angabe die gesetzlichen Standardregeln),
 * seiner Regeln (Schlüssel aus dem gewählten Satz) und der Vertragsvorlagen, denen sie zugeordnet
 * werden.
 */
class StoreLegalDefaultShiftRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $set = $this->input('set');
        $set = is_string($set) && $set !== '' ? $set : ShiftRuleSets::LEGAL;

        return [
            'set' => ['nullable', 'string', Rule::in(ShiftRuleSets::keys())],
            'rules' => 'required|array|min:1',
            'rules.*' => ['string', 'distinct', Rule::in(ShiftRuleSets::ruleKeys($set))],
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'integer|exists:user_contracts,id',
        ];
    }
}
