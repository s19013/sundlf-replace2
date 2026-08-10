<?php

namespace App\Models\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @phpstan-require-extends Model
 *
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
trait DetectsStaleData
{
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
}
