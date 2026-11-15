<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tag */
class TagResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, count: int, created_at: \Illuminate\Support\Carbon|null, edited_at: \Illuminate\Support\Carbon}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'count' => (int) $this->status?->count,
            'created_at' => $this->created_at,
            'edited_at' => $this->edited_at,
        ];
    }
}
