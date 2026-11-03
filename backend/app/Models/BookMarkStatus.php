<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ブックマークのうち、ユーザーが直接変更しない項目(閲覧数・タグもちフラグ)を管理する。
 * ブックマークの`updated_at`に影響させずに更新するため、本体とは別テーブルにしている。
 */
class BookMarkStatus extends Model
{
    /**
     * ブックマーク1件につき1行なので、ブックマークのidを主キーにする。
     */
    protected $primaryKey = 'book_mark_id';

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
     * @return BelongsTo<BookMark, $this>
     */
    public function bookMark(): BelongsTo
    {
        return $this->belongsTo(BookMark::class)->withTrashed();
    }
}
