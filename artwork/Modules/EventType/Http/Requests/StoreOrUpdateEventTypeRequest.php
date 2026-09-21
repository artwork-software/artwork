<?php

namespace Artwork\Modules\EventType\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sicherheits-Audit 21.09.2026 (E, MITTEL): verification_mode und specific_verifier_id kamen ungeprüft
 * in die Terminart — ein ungültiger Modus schaltete die Verifizierung still ab
 * (EventVerificationService kennt nur none/specific/any/all).
 */
class StoreOrUpdateEventTypeRequest extends FormRequest
{
    public const VERIFICATION_MODES = ['none', 'specific', 'any', 'all'];

    /**
     * Rechteprüfung liegt auf der Routengruppe (can:change event settings).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $requiredOnCreate = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$requiredOnCreate, 'string', 'max:255'],
            'hex_code' => ['nullable', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:255'],
            'project_mandatory' => ['nullable', 'boolean'],
            'individual_name' => ['nullable', 'boolean'],
            'relevant_for_project_period' => ['nullable', 'boolean'],
            'verification_mode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::in(self::VERIFICATION_MODES)],
            'specific_verifier_id' => ['nullable', 'integer', 'exists:users,id'],
            'users' => ['nullable', 'array'],
            'users.*.id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
