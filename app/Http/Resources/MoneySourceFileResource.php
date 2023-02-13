<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneySourceFileResource extends JsonResource
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
            "name" => $this->name,
            "basename" => $this->basename,
            "money_source_id" => $this->money_source_id,
            "created_at" => $this->created_at,
            "comments" => CommentResource::collection($this->comments),
        ];
    }
}
