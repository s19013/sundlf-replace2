<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * ゴミ箱(論理削除)機能を持つ、ユーザー所有リソースに共通する振る舞い。
 *
 * `Model` を継承し `SoftDeletes` を使用するクラスでの利用を前提とする。
 *
 * @phpstan-require-extends Model
 *
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method bool trashed()
 * @method bool restore()
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
                ->isNowOrPast();
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

    /**
     * ゴミ箱から復元する。
     */
    public function salvage(): bool
    {
        return $this->restore();
    }
}
