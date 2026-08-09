<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $article_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MemoTag extends Pivot
{
    /**
     * 命名規則上の推測テーブル名(memo_tags)と実テーブル名が異なるため明示する。
     */
    protected $table = 'article_tags';
}
