<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Artwork\Modules\AiAssistant\Services\AiAssistantAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAssistantSettingsRequest extends FormRequest
{
    /**
     * Hausweite KI-Schalter: KI-Freischaltung der Person und das Recht, Tool-Einstellungen zu ändern
     * (wie bei allen anderen Tool-Einstellungen; Admins über Gate::before).
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return app(AiAssistantAccess::class)->visibleTo($user)
            && $user !== null
            && $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'level_b_enabled' => ['required', 'boolean'],
            'level_c_enabled' => ['required', 'boolean'],
            'transcript_retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ];
    }

    /**
     * Stufe C (Aktionen) ohne Stufe B (Datenauswertung) ergibt keinen Zustand, den der Assistent
     * bedienen kann — er duerfte schreiben, ohne lesen zu duerfen. C1 §2.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('level_c_enabled') && !$this->boolean('level_b_enabled')) {
                $validator->errors()->add(
                    'level_c_enabled',
                    __('Stage "actions" requires stage "data analysis".')
                );
            }
        });
    }
}
