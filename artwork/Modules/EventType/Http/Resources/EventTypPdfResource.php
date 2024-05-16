<?php

namespace Artwork\Modules\EventType\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventTypPdfResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'hex_code' => $this->hex_code,
        ];
    }
}
