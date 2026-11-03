<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * メモのうち、ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理する。
 * メモの`updated_at`に影響させずに更新するため、本体とは別テーブルにしている。
 */
class MemoStatus extends Model
{
    /**
     * 旧システムでは`article`という名前だった名残でテーブル名がクラス名の規約と一致しない。
     */
    protected $table = 'article_statuses';

    /**
     * メモ1件につき1行なので、メモのidを主キーにする。
     */
    protected $primaryKey = 'article_id';

    public $incrementing = false;

    public const UPDATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'has_tags' => false,
        'count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_tags' => 'boolean',
            'count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Memo, $this>
     */
    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class, 'article_id')->withTrashed();
    }
}
