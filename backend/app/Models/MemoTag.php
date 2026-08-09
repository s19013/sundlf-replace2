<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MemoTag extends Pivot
{
    /**
     * 命名規則上の推測テーブル名(memo_tags)と実テーブル名が異なるため明示する。
     */
    protected $table = 'article_tags';
}
