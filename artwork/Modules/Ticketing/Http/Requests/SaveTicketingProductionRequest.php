<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingProductionImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Auftritt des Projekts im Ticketshop; das Titelbild kommt als Datei mit (hero) oder ist ein
 * vorhandenes weiteres Bild (cover_image_id), remove_hero nimmt das bisherige weg.
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
            'cover_image_id' => 'nullable|integer|prohibits:hero',
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

                $kept = $this->existingImages()->whereNotIn('id', $this->input('remove_image_ids', []));
                $coverImageId = $this->input('cover_image_id');

                if ($coverImageId !== null && !(clone $kept)->whereKey($coverImageId)->exists()) {
                    $validator->errors()->add('cover_image_id', __('The chosen cover picture no longer exists.'));

                    return;
                }

                // Ein neues Titelbild schiebt das bisherige zu den weiteren Bildern, ein befördertes verlässt sie.
                $hasHero = $this->existingProduction()?->hero_path !== null;
                $newCover = $this->hasFile('hero') || $coverImageId !== null;
                $demoted = $hasHero && $newCover && !$this->boolean('remove_hero') ? 1 : 0;
                $promoted = $coverImageId !== null ? 1 : 0;
                $total = $kept->count() + count($this->file('images', [])) + $demoted - $promoted;

                if ($total > TicketingProduction::MAX_IMAGES) {
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

    public function coverImageId(): ?int
    {
        $id = $this->validated('cover_image_id');

        return $id === null ? null : (int) $id;
    }

    private function existingProduction(): ?TicketingProduction
    {
        return TicketingProduction::query()->where('project_id', $this->project()->id)->first();
    }

    /** @return Builder<TicketingProductionImage> */
    private function existingImages(): Builder
    {
        return TicketingProductionImage::query()
            ->whereHas('production', fn (Builder $query): Builder => $query->where('project_id', $this->project()->id));
    }

    private function project(): Project
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $project;
    }
}
