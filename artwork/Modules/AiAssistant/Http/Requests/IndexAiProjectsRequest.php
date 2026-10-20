<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/projects (core-api-context-projects; Konzept B1 §3–4).
 *
 * `from`/`to` sind ein optionaler Zeitraumfilter, aber nur gemeinsam gueltig und hoechstens
 * `ai.max_range_days` lang. Sie sind bewusst nicht Pflicht: die Projektsuche ist in erster Linie
 * eine Namenssuche (`search_projects`, B1 §3); ein Pflichtzeitraum wuerde sie unbrauchbar machen.
 * Fuer die zeitbezogenen Endpunkte (Termine, Belegung, Dienstplan) gilt die Pflicht weiterhin.
 */
class IndexAiProjectsRequest extends FormRequest
{
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
            'q' => ['nullable', 'string', 'max:200'],
            'state' => ['nullable', 'integer'],
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
            'limit' => ['nullable', 'integer', 'min:1'],
            // ai-scenarios-4 S18 (additiv)
            'budget_deadline_from' => ['nullable', 'date_format:Y-m-d'],
            'budget_deadline_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:budget_deadline_from'],
            'state_id' => ['nullable', 'integer'],
            'marked_as_done' => ['nullable', 'boolean'],
            'manager_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->query('from');
            $to = $this->query('to');

            if (!is_string($from) || !is_string($to)) {
                return;
            }

            $maxDays = (int) config('ai.max_range_days');

            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > $maxDays) {
                $validator->errors()->add('to', "Der Zeitraum darf hoechstens {$maxDays} Tage umfassen.");
            }
        });
    }
}
