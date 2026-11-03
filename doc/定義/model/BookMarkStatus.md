# テーブル 属性

[book_mark_statuses.md](../db/book_mark_statuses.md)

主キーは `book_mark_id`。自動採番しないため `$primaryKey = 'book_mark_id'`、`$incrementing = false` とする。

`updated_at` を持たないため、`UPDATED_AT = null` とする。

status行に共通する設定(`$incrementing`・`UPDATED_AT`・`count` の既定値とキャスト)は抽象基底クラス `StatusRow` に、`MemoStatus` / `BookMarkStatus` に共通する設定(`has_tags` の既定値とキャスト・`tags()`)は `StatusRow` を継承した抽象基底クラス `EntryStatus` にまとめている。サブクラスは `$primaryKey`、中間テーブル名(`tagPivotTable()`)、親へのリレーションだけを持つ。

# 追加属性

なし

# 関数

なし

# リレーション

ほかはテーブル定義を参照

## bookMark

`book_marks` を belongsTo で取得

## tags

中間テーブルを使い、本体に紐付いているタグを belongsToMany で取得する。主キーが本体の id と同じなので、中間テーブルをそのまま使える。`has_tags` を1文の UPDATE で判定するために使う。

# 備考

ブックマーク作成時に、同じトランザクション内で作成する(`HasStatusRow` トレイトの created イベント)。イベントを通さずにブックマークを作る場合は、呼び出し側で作成すること。
