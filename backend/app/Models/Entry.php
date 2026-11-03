<?php

namespace App\Models;

use App\Models\Concerns\DetectsStaleData;
use App\Models\Concerns\HasIncrementableCount;
use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasStatusRow;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class Entry extends Model
{
    use DetectsStaleData,
        HasIncrementableCount,
        HasOwner,
        HasStatusRow,
        SoftDeletes,
        Trashable;

    /**
     * ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理するstatus行。
     *
     * @return HasOne<covariant Model, $this>
     */
    abstract public function status(): HasOne;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'star' => 'integer',
        ];
    }
}
