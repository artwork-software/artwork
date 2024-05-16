<?php

namespace Artwork\Modules\ContractModule\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
