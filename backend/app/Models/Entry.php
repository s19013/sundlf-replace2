<?php

namespace App\Models;

use App\Models\Concerns\DetectsStaleData;
use App\Models\Concerns\HasIncrementableCount;
use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasStatusRow;
use App\Models\Concerns\TracksEditedAt;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class Entry extends Model
{
    use DetectsStaleData,
        HasIncrementableCount,
        HasOwner,
        HasStatusRow,
        SoftDeletes,
        TracksEditedAt,
        Trashable;

    /**
     * `has_tags`は中間モデルのイベント(SyncsEntryHasTags)とタグ削除(DeleteTagUsecase)だけが書くため、$fillableには含めない。
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'has_tags' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'star' => 'integer',
            'has_tags' => 'boolean',
        ];
    }
}
