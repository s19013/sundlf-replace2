# テーブル 属性

[tags](../db/tags.md)

count(使用回数)は [tag_statuses.md](../db/tag_statuses.md) で管理する

edited_at(編集日時)は datetime にキャストする。`updated_at`(行の更新日時)との違いは [tags.md](../db/tags.md) を参照。

# 追加属性

なし

# 関数

## isOwner

### 引数

- string:userId

user_id と 引数userId が同じなら true

## editableAttributes

編集とみなす属性。タグ名(`name`)だけ。これが変わった保存で `edited_at` を更新する(`TracksEditedAt` トレイト)。
`TracksEditedAt` の既定の実装で、`$fillable` のうち `user_id` 以外が編集対象になる。
タグ名の変更(`UpdateTagUsecase`)で同じ名前を送った場合は変更なしとなり、何も書かない。

# リレーション

ほかはテーブル定義を参照

## status

`tag_statuses` を hasOne で取得

# 備考

## count(使用回数)の増減

増減の対象は `tag_statuses.count`。`tags` 側を更新しないので、`tags.updated_at` も `tags.edited_at` も変わらない(タグ検索の編集日時順に影響しない)。

当初は「リポジトリに作成予定」としていたが、実装ではモデルやリポジトリにメソッドは作らず、
メモ関連のUsecase内で増減処理(`increment('count')` / `decrement('count')`)を直接呼んでいる。
(複数のタグをまとめて増減したいため)

| タイミング | 増減                                       | 実装場所                                        |
| ---------- | ------------------------------------------ | ----------------------------------------------- |
| 作成       | 付与されたタグを +1                        | `Usecases/Memo/Concerns/SyncsMemoTags.php`      |
| 更新       | 新しく付与されたタグを +1、外れたタグを -1 | `Usecases/Memo/Concerns/SyncsMemoTags.php`      |
| 論理削除   | 紐付いている全タグを -1                    | `Usecases/Memo/DeleteMemoUsecase.php`           |
| 復元       | 紐付いている全タグを +1                    | `Usecases/Memo/SalvageMemoUsecase.php`          |
| 完全削除   | 変化なし(論理削除時に減算済みのため)       | `Usecases/Memo/CompletelyDeleteMemoUsecase.php` |

※ 作成・更新時は、増減の対象を「紐付け状態が実際に変化したタグ」だけに絞っている(変化のないタグは増減しない)。
