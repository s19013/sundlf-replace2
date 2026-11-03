<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ブックマークのうち、ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理する。
 */
class BookMarkStatus extends EntryStatus
{
    protected $primaryKey = 'book_mark_id';

    protected function tagPivotTable(): string
    {
        return 'book_mark_tags';
    }

    /**
     * @return BelongsTo<BookMark, $this>
     */
    public function bookMark(): BelongsTo
    {
        return $this->belongsTo(BookMark::class)->withTrashed();
    }
}
