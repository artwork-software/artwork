<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Künstlerplan: Spielstätte, Künstleraufenthalt (Hotelangaben) und die ausgewählten Termine des Projekts.
 * Die Terminarten werden als Vorauswahl für den nächsten Export gespeichert.
 */
class ProjectArtistPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : 0;

        return [
            'venue_room_id' => ['nullable', 'integer', 'exists:rooms,id,deleted_at,NULL'],
            'artist_residency_id' => [
                'nullable',
                'integer',
                Rule::exists('artist_residencies', 'id')->where('project_id', $projectId),
            ],
            'event_type_ids' => ['nullable', 'array'],
            'event_type_ids.*' => ['integer', 'exists:event_types,id'],
            'event_ids' => ['nullable', 'array'],
            'event_ids.*' => [
                'integer',
                Rule::exists('events', 'id')->where('project_id', $projectId)->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function eventIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('event_ids', []))));
    }

    /**
     * @return array<int, int>
     */
    public function eventTypeIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('event_type_ids', []))));
    }
}
