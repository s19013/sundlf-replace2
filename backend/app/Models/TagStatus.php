<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * タグのうち、ユーザーが直接変更しない項目(使用回数)を管理する。
 * タグの`updated_at`に影響させずに更新するため、本体とは別テーブルにしている。
 */
class TagStatus extends Model
{
    /**
     * タグ1件につき1行なので、タグのidを主キーにする。
     */
    protected $primaryKey = 'tag_id';

    public $incrementing = false;

    public const UPDATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
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
            'count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
