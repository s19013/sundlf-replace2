<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Memo */
class MemoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'body' => (string) $this->body,
            'star' => (int) $this->star,
            'count' => (int) $this->count,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'is_in_trash' => (bool) $this->is_in_trash,
            'is_deadline_approaching' => (bool) $this->is_deadline_approaching,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
