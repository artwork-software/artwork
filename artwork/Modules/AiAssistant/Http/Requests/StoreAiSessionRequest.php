<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Illuminate\Contracts\Validation\Validator;
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
            'scopes.*' => ['string', Rule::in([AiScopes::TRANSCRIPT, AiScopes::READ, AiScopes::WRITE])],
            // ai:write gibt es nur zusammen mit einer bestaetigten Aktion (contracts/token-contract.md,
            // Konzept W2 §2) — nie implizit.
            'action_id' => ['nullable', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->wantsWriteScope()) {
                return;
            }

            if (!is_string($this->input('action_id'))) {
                $validator->errors()->add('action_id', __('This field is required.'));
            }
        });
    }

    public function wantsWriteScope(): bool
    {
        return in_array(AiScopes::WRITE, (array) $this->input('scopes', []), true);
    }

    public function actionId(): ?string
    {
        $actionId = $this->input('action_id');

        return is_string($actionId) && $actionId !== '' ? $actionId : null;
    }
}
