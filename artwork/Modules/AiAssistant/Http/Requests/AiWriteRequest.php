<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gemeinsame Regeln der Schreib-Endpunkte (Konzept W1 §2).
 *
 * `dry_run` ist **true**, wenn nichts anderes dasteht: ein fehlendes Feld darf nie zur Ausfuehrung
 * fuehren. `action_id` ist nur bei `dry_run=false` Pflicht — die Pruefung selbst macht der
 * ActionGuard, hier faellt nur das fehlende Feld auf (422).
 */
abstract class AiWriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function writeRules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'action_id' => ['nullable', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->isDryRun() && $this->actionId() === null) {
                $validator->errors()->add('action_id', 'Ausfuehrung ist nur mit einer bestaetigten Aktion moeglich.');
            }
        });
    }

    public function isDryRun(): bool
    {
        return $this->boolean('dry_run', true);
    }

    public function actionId(): ?string
    {
        $actionId = $this->input('action_id');

        return is_string($actionId) && $actionId !== '' ? $actionId : null;
    }
}
