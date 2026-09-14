<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiSessionRequest extends FormRequest
{
    /**
     * Bewusst der Service und kein Gate: Gate::before in app/Providers/AuthServiceProvider.php
     * gibt Artwork-Admins jede Gate-Abfrage frei — ein Gate wuerde ihnen auch bei abgeschaltetem
     * Haus ein Token verschaffen.
     */
    public function authorize(): bool
    {
        return app(AiAssistantAccess::class)->allowed($this->user());
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scopes' => ['sometimes', 'array'],
            // ai:write fehlt hier absichtlich: der Schreibpfad braucht eine Aktions-Bindung
            // (action_id) und kommt mit W2. Bis dahin ist ai:write im Request ein 422.
            'scopes.*' => ['string', Rule::in([AiScopes::TRANSCRIPT, AiScopes::READ])],
        ];
    }
}
