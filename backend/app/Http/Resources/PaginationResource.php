<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Illuminate\Pagination\LengthAwarePaginator<int, \Illuminate\Database\Eloquent\Model> */
class PaginationResource extends JsonResource
{
    /**
     * @return array{current_page: int, last_page: int, per_page: int, total: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'current_page' => $this->currentPage(),
            'last_page' => $this->lastPage(),
            'per_page' => $this->perPage(),
            'total' => $this->total(),
        ];
    }
}
