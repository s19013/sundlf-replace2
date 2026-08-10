<?php

namespace App\Models\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ゴミ箱(論理削除)機能を持つ、ユーザー所有リソースに共通する振る舞い。
 *
 * `Model` を継承し `SoftDeletes` を使用するクラスでの利用を前提とする。
 *
 * @phpstan-require-extends Model
 *
 * @property int $user_id
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method bool trashed()
 * @method bool restore()
 * @method static mixed withoutTimestamps(callable $callback)
 */
trait Trashable
{
    /**
     * ゴミ箱に入ってから完全削除されるまでの日数。
     */
    public const DAYS_UNTIL_PERMANENT_DELETE = 30;

    /**
     * 完全削除の何日前から期限警告を出すか。
     */
    public const DEADLINE_WARNING_DAYS = 7;

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
        try {
            $fetchedAtTime = Carbon::parse($fetchedAt);
        } catch (InvalidFormatException) {
            // 比較できない場合は更新済みとみなし、上書きを防ぐ。
            return true;
        }

        return $this->updated_at?->gt($fetchedAtTime) ?? false;
    }

    /**
     * ゴミ箱から復元する。
     */
    public function salvage(): bool
    {
        return $this->restore();
    }
}
