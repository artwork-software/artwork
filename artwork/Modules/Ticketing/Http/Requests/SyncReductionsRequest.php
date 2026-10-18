<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Erneuter Abgleich der Ermäßigungen aus dem Tab "Ermäßigungen". */
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
        return TicketingDraftRules::reductions();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => TicketingDraftRules::checkReductions(
            $validator,
            $this->input('reductions', []),
        ));
    }
}
