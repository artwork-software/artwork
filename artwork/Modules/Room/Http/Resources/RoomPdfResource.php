<?php

namespace Artwork\Modules\Room\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomPdfResource extends JsonResource
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
            'name' => $this->name
        ];
    }
}
