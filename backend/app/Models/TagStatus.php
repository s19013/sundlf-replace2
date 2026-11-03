<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * タグのうち、ユーザーが直接変更しない項目(使用回数)を管理する。
 */
class TagStatus extends StatusRow
{
    protected $primaryKey = 'tag_id';

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
