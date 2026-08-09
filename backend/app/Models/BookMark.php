<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class BookMark extends Model
{
    /** @use HasFactory<\Database\Factories\BookMarkFactory> */
    use HasFactory, SoftDeletes;

    /**
     * ゴミ箱に入ってから完全削除されるまでの日数。
     */
    public const DAYS_UNTIL_PERMANENT_DELETE = 30;

    /**
     * 完全削除の何日前から期限警告を出すか。
     */
    public const DEADLINE_WARNING_DAYS = 7;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'star',
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
            'star' => 'integer',
            'has_tags' => 'boolean',
        ];
    }

    /**
     * 期限間近(完全削除まで残りDEADLINE_WARNING_DAYS日以内)かどうか。
     *
     * @return Attribute<bool, never>
     */
    protected function isDeadlineApproaching(): Attribute
    {
        return Attribute::make(get: function (): bool {
            if ($this->deleted_at === null) {
                return false;
            }

            return $this->deleted_at->copy()
                ->addDays(self::DAYS_UNTIL_PERMANENT_DELETE - self::DEADLINE_WARNING_DAYS)
                ->isPast();
        });
    }

    /**
     * ゴミ箱(論理削除済み)かどうか。
     *
     * @return Attribute<bool, never>
     */
    protected function isInTrash(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->trashed());
    }

    public function isOwner(string $userId): bool
    {
        return (string) $this->user_id === $userId;
    }

    /**
     * count を +1 する。検索への影響を避けるため updated_at は更新しない。
     */
    public function countIncrease(): void
    {
        static::withoutTimestamps(fn () => $this->increment('count'));
    }

    /**
     * データベース上のデータが、自分が取得した後に更新されていないか確認する。
     */
    public function hasBeenUpdatedSinceRetrieval(string $fetchedAt): bool
    {
        return $this->updated_at?->gt(Carbon::parse($fetchedAt)) ?? false;
    }

    /**
     * ゴミ箱から復元する。
     */
    public function salvage(): bool
    {
        return $this->restore();
    }

    /**
     * @return BelongsToMany<Tag, $this, BookMarkTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'book_mark_tags', 'book_mark_id', 'tag_id')
            ->using(BookMarkTag::class)
            ->withTimestamps();
    }
}
