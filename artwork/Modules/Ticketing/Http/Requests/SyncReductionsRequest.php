<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Die Ermäßigungen aus dem Tab "Ermäßigungen": die Liste wird angelegt oder geändert, removed_ids sind
 * die geladenen, die herausgenommen wurden — nur die löscht tickets.
 */
class SyncReductionsRequest extends FormRequest
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
            ...TicketingDraftRules::reductions(),
            'removed_ids' => 'present|array|max:50',
            'removed_ids.*' => 'uuid',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => TicketingDraftRules::checkReductions(
            $validator,
            $this->input('reductions', []),
        ));
    }
}
