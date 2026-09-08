<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Shift\Support\LegalDefaultShiftRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Gesetzliche Standardregeln anlegen": Auswahl der Standardregeln (Schlüssel aus
 * LegalDefaultShiftRules) und der Vertragsvorlagen, denen sie zugeordnet werden.
 */
class StoreLegalDefaultShiftRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rules' => 'required|array|min:1',
            'rules.*' => ['string', 'distinct', Rule::in(LegalDefaultShiftRules::keys())],
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'integer|exists:user_contracts,id',
        ];
    }
}
