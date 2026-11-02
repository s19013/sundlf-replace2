<?php

namespace App\Models;

use App\Models\Concerns\SyncsEntryHasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $article_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MemoTag extends Pivot
{
    use SyncsEntryHasTags;

    /**
     * 命名規則上の推測テーブル名(memo_tags)と実テーブル名が異なるため明示する。
     */
    protected $table = 'article_tags';

    /**
     * @return Builder<Memo>
     */
    protected function parentEntryQuery(): Builder
    {
        return Memo::withTrashed()->whereKey($this->article_id);
    }
}
