<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

/**
 * GET /api/v1/ai/rooms (core-api-lookups; Konzept B1 §3).
 *
 * core-ai-series-rooms: `category` (Teil des Namens) bzw. `category_id` filtern nach Raumkategorie —
 * „alle Raeume der Kategorie Meetingraum" (Feedback 17.11.).
 */
class IndexAiRoomsRequest extends AiLookupRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
        ]);
    }

    public function category(): ?string
    {
        $category = $this->validated('category');

        return is_string($category) && $category !== '' ? $category : null;
    }

    public function categoryId(): ?int
    {
        $categoryId = $this->validated('category_id');

        return $categoryId === null ? null : (int) $categoryId;
    }
}
