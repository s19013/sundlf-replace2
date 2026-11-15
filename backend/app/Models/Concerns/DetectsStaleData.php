<?php

namespace App\Models\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @phpstan-require-extends Model
 *
 * @property \Illuminate\Support\Carbon $edited_at
 */
trait DetectsStaleData
{
    /**
     * 自分が取得した後に、ユーザーによる編集(`edited_at`)が行われていないか確認する。
     * 編集以外の更新(has_tagsの同期など)は`updated_at`だけが変わるため、競合とみなさない。
     */
    public function hasBeenEditedSinceRetrieval(string $fetchedAt): bool
    {
        try {
            $fetchedAtTime = Carbon::parse($fetchedAt);
        } catch (InvalidFormatException) {
            // 比較できない場合は編集済みとみなし、上書きを防ぐ。
            return true;
        }

        return $this->edited_at->gt($fetchedAtTime);
    }
}
