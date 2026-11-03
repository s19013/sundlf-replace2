<?php

namespace App\Models;

use App\Models\Concerns\SyncsEntryHasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $book_mark_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BookMarkTag extends Pivot
{
    use SyncsEntryHasTags;

    protected $table = 'book_mark_tags';

    /**
     * @return Builder<BookMarkStatus>
     */
    protected function parentStatusQuery(): Builder
    {
        return BookMarkStatus::whereKey($this->book_mark_id);
    }
}
