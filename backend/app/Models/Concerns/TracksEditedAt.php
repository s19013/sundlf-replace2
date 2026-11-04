<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * 作成時と、ユーザーが内容を編集した保存のときだけ、`edited_at`を`created_at`・`updated_at`と同じ時刻で設定する。
 *
 * `updated_at`は行の更新日時で、編集以外の更新(has_tagsの同期など)でも変わる。
 * クエリビルダーの`update()`・SoftDeletesの論理削除・status行の更新は`updateTimestamps()`を通らないため、`edited_at`は変わらない。
 * 編集対象の属性を追加した場合は、`editableAttributes()`も更新すること。
 *
 * @phpstan-require-extends Model
 */
trait TracksEditedAt
{
    /**
     * 次の`save()`を、編集対象の属性が変わっていなくても編集として扱うか。
     */
    protected bool $isMarkedAsEdited = false;

    /**
     * 編集とみなす属性。これらが変更された保存で`edited_at`を更新する。
     *
     * @return list<string>
     */
    abstract protected function editableAttributes(): array;

    protected static function bootTracksEditedAt(): void
    {
        static::saved(function (self $model): void {
            $model->isMarkedAsEdited = false;
        });
    }

    /**
     * 本体の属性が変わらない編集(タグの付け替えなど)を、次の`save()`で編集として扱う。
     * モデルをdirtyにするため、`save()`は必ずUPDATEを発行する。
     */
    public function markAsEdited(): static
    {
        $this->isMarkedAsEdited = true;

        // dirtyにするための仮の値。保存時にupdateTimestamps()でupdated_atと同じ時刻に揃える
        $this->setAttribute('edited_at', $this->freshTimestamp());

        return $this;
    }

    /**
     * Eloquentのタイムスタンプ更新に合わせて、`edited_at`も同じ時刻で設定する。
     */
    public function updateTimestamps(): static
    {
        parent::updateTimestamps();

        // 新規作成時はcreated_at、編集時はupdated_atと同じ時刻にする
        $sourceColumn = $this->exists ? $this->getUpdatedAtColumn() : $this->getCreatedAtColumn();

        if ($sourceColumn !== null && $this->shouldStampEditedAt()) {
            $this->setAttribute('edited_at', $this->getAttribute($sourceColumn));
        }

        return $this;
    }

    /**
     * 今回の保存で`edited_at`を設定するか。呼び出し側が`edited_at`を明示した場合(テスト・データ移行用)はその値を優先する。
     */
    private function shouldStampEditedAt(): bool
    {
        if ($this->isMarkedAsEdited) {
            return true;
        }

        if ($this->isDirty('edited_at')) {
            return false;
        }

        return ! $this->exists || $this->isDirty($this->editableAttributes());
    }
}
