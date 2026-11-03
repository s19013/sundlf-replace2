<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * エントリー(メモ・ブックマーク)のうち、ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理するstatus行の基底クラス。
 * エントリーの`updated_at`に影響させずに更新するため、本体とは別テーブルにしている。
 * エントリー1件につき1行なので、サブクラスでエントリーのidを主キー(`$primaryKey`)に指定する。
 */
abstract class EntryStatus extends Model
{
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
     * エントリーとタグの中間テーブル名。
     */
    abstract protected function tagPivotTable(): string;

    /**
     * 属性のキャスト定義。
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
     * 本体に紐付いているタグ。主キーが本体のidと同じなので、中間テーブルをそのまま使える。
     * `has_tags`を1文のUPDATEで判定するために使う。
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, $this->tagPivotTable(), $this->getKeyName(), 'tag_id');
    }
}
