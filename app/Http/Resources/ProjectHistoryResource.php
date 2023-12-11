<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectHistoryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'created_at' => $this->created_at->diffInHours() < 24
                ? $this->created_at->diffForHumans()
                : $this->created_at->format('d.m.Y, H:i'),
            'user' => $this->user,
            'description' => $this->description
        ];
    }
}
