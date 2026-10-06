<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingProductionImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Auftritt des Projekts im Ticketshop; das Bild kommt als Datei mit, remove_hero nimmt es weg.
 * Weitere Bilder kommen als images[], remove_image_ids[] nimmt vorhandene heraus.
 */
class SaveTicketingProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Die Auswahl kommt als JSON-String im Formular mit, damit auch "keine" ankommt. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reduction_type_ids'))) {
            $this->merge(['reduction_type_ids' => json_decode($this->input('reduction_type_ids'), true)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:4000',
            'reduction_type_ids' => 'nullable|array|max:50',
            'reduction_type_ids.*' => 'required|uuid',
            'hero' => 'nullable|image|max:8192',
            'remove_hero' => 'sometimes|boolean',
            'images' => 'nullable|array|max:' . TicketingProduction::MAX_IMAGES,
            'images.*' => 'required|image|max:8192',
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => 'required|integer',
        ];
    }

    /**
     * Was bleibt und was dazukommt, darf zusammen die Obergrenze in tickets nicht überschreiten.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kept = $this->existingImages()->whereNotIn('id', $this->input('remove_image_ids', []))->count();

                if ($kept + count($this->file('images', [])) > TicketingProduction::MAX_IMAGES) {
                    $validator->errors()->add(
                        'images',
                        __('At most :max further pictures per production.', ['max' => TicketingProduction::MAX_IMAGES]),
                    );
                }
            },
        ];
    }

    /** @return list<int> */
    public function removeImageIds(): array
    {
        return array_map('intval', $this->validated('remove_image_ids') ?? []);
    }

    /** @return Builder<TicketingProductionImage> */
    private function existingImages(): Builder
    {
        /** @var Project $project */
        $project = $this->route('project');

        return TicketingProductionImage::query()
            ->whereHas('production', fn (Builder $query): Builder => $query->where('project_id', $project->id));
    }
}
