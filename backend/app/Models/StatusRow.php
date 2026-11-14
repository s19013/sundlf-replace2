<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 本体(メモ・ブックマーク・タグ)のうち、ユーザーが直接変更しない`count`(閲覧数・使用回数)を管理するstatus行の基底クラス。
 * `count`は書き込みが頻繁で、編集日時(`edited_at`)とも検索の絞り込みとも関係しないため、本体とは別テーブルにしている。
 * 本体1件につき1行なので、サブクラスで本体のidを主キー(`$primaryKey`)に指定する。
 */
abstract class StatusRow extends Model
{
    public $incrementing = false;

    public const UPDATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'count' => 0,
    ];

    /**
     * 属性のキャスト定義。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
