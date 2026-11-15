# テーブル 属性

[book_mark.md](../db/book_marks.md)

count(閲覧数)は [book_mark_statuses.md](../db/book_mark_statuses.md) で管理する

has_tags(タグもちフラグ)は本体で持つ。既定値は false、boolean にキャストする。中間モデルのイベントとタグ削除だけが書くため、`$fillable` には含めない。

edited_at(編集日時)は datetime にキャストする。`updated_at`(行の更新日時)との違いは [book_marks.md](../db/book_marks.md) を参照。

# 追加属性

## isDeadlineApproaching

期限間近かどうか

deleted_at->copy()->addDays(削除までの期間 - 通知を出す日数)->isPast();

## isInTrash

論理削除済みかを判断

deleted_at IS NOT NULLの場合true

# 関数

## isOwner

### 引数

- string:userId

user_id と 引数userId が同じなら true

## countIncrease

`book_mark_statuses.count` を +1

※ status テーブル側を更新するので、本体の updated_at も edited_at も変わらない

## hasBeenEditedSinceRetrieval

### 引数

- string:fetched_at

`edited_at` > `fetched_at` の 場合 true

自分が取得した後に、ユーザーによる編集が行われていないか確認する(楽観的排他制御)。
`updated_at` だけが変わる編集以外の更新(has_tags の同期など)は、競合とみなさない。
`fetched_at` を解析できない場合は、上書きを防ぐため true を返す。

## markAsEdited

本体の属性が変わらない編集(タグの付け替え)を、次の `save()` で編集として扱う。`save()` で `edited_at` が `updated_at` と同じ時刻になる。印は保存後に消える。

## editableAttributes

編集とみなす属性。タイトル(`title`)・URL(`url`)・星(`star`)。これらが変わった保存で `edited_at` を更新する。
`TracksEditedAt` の既定の実装で、`$fillable` のうち `user_id` 以外が編集対象になる。`$fillable` に属性を追加すれば、編集対象にも加わる。

`edited_at` の設定は `TracksEditedAt` トレイト(`backend/app/Models/Concerns/TracksEditedAt.php`)が行う。

- 作成時: `created_at` と同じ時刻(呼び出し側が明示した場合はその値)
- 編集対象の属性が変わった保存、または `markAsEdited()` 後の保存: `updated_at` と同じ時刻
- それ以外の保存、クエリビルダーの `update()`、論理削除(`delete()`)、status テーブルの更新: 変えない

## salvage

deleted_at を nullにする

# リレーション

ほかはテーブル定義を参照

## tags

中間テーブル(book_mark_tags) 経由でタグを取得

## status

`book_mark_statuses` を hasOne で取得

# 備考

## 状態

### 通常

通常の一覧･検索･閲覧･編集に表示される。
ゴミ箱関連の画面では表示されない。

### ゴミ箱

論理扱いされている状態
通常の一覧･検索･閲覧･編集には表示されない。
ゴミ箱関連の画面でのみ表示される。

### 完全削除済み

DBから削除され、復元できない。
