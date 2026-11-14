<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ブックマークのうち、ユーザーが直接変更しない項目(閲覧数)を管理する。
 */
class BookMarkStatus extends StatusRow
{
    protected $primaryKey = 'book_mark_id';

    /**
     * @return BelongsTo<BookMark, $this>
     */
    public function bookMark(): BelongsTo
    {
        return $this->belongsTo(BookMark::class)->withTrashed();
    }
}
