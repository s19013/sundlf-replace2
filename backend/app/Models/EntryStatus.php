<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * エントリー(メモ・ブックマーク)のstatus行(閲覧数・タグもちフラグ)の基底クラス。
 */
abstract class EntryStatus extends StatusRow
{
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
            ...parent::casts(),
            'has_tags' => 'boolean',
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
