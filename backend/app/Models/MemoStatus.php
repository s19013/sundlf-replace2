<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * メモのうち、ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理する。
 */
class MemoStatus extends EntryStatus
{
    /**
     * 旧システムでは`article`という名前だった名残でテーブル名がクラス名の規約と一致しない。
     */
    protected $table = 'article_statuses';

    protected $primaryKey = 'article_id';

    protected function tagPivotTable(): string
    {
        return 'article_tags';
    }

    /**
     * @return BelongsTo<Memo, $this>
     */
    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class, 'article_id')->withTrashed();
    }
}
