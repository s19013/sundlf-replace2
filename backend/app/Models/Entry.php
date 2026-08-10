<?php

namespace App\Models;

use App\Models\Concerns\DetectsStaleData;
use App\Models\Concerns\HasIncrementableCount;
use App\Models\Concerns\HasOwner;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class Entry extends Model
{
    use DetectsStaleData,
        HasIncrementableCount,
        HasOwner,
        SoftDeletes,
        Trashable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'star' => 'integer',
            'has_tags' => 'boolean',
        ];
    }
}
