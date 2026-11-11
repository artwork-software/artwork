<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/shifts/{shift}/candidates?shift_qualification_id= (Erweiterung 2 C). Ohne Funktion
 * gilt die erste Funktion der Schicht mit offenem Bedarf.
 */
class IndexAiShiftCandidatesRequest extends FormRequest
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
            'shift_qualification_id' => ['nullable', 'integer'],
        ];
    }

    public function qualificationId(): ?int
    {
        $id = $this->validated('shift_qualification_id');

        return $id === null ? null : (int) $id;
    }
}
