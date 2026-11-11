<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gemeinsame Regeln der Nachschlage-Endpunkte (core-api-lookups; Konzept B1 §3).
 *
 * Anders als die Aggregations-Endpunkte tragen sie **keinen** Zeitraum: nachgeschlagen werden
 * Stammdaten, und `AiTimeRangeRequest` wuerde hier einen Pflichtzeitraum erzwingen, den es nicht
 * gibt. Die Obergrenze je Liste bleibt dieselbe (`ai.list_limit`).
 */
abstract class AiLookupRequest extends FormRequest
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
            'q' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): ?string
    {
        $search = $this->validated('q');

        return is_string($search) && $search !== '' ? $search : null;
    }

    public function limit(): ?int
    {
        $limit = $this->validated('limit');

        return $limit === null ? null : (int) $limit;
    }
}
