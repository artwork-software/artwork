<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "text" => $this->text,
            "project_id" => $this->project_id,
            "project_file_id" => $this->project_file_id,
            "money_source_file_id" => $this->money_source_file_id,
            "contract_id" => $this->contract_id,
            "user" => UserIconResource::make($this->user),
            "created_at" => $this->created_at->format('d.m.Y, H:i'),
        ];
    }
}
