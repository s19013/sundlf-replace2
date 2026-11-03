<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 本体(メモ・ブックマーク・タグ)のうち、ユーザーが直接変更しない項目を管理するstatus行の基底クラス。
 * 本体の`updated_at`に影響させずに更新するため、本体とは別テーブルにしている。
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
