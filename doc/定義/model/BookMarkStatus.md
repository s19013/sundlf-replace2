# テーブル 属性

[book_mark_statuses.md](../db/book_mark_statuses.md)

主キーは `book_mark_id`。自動採番しないため `$primaryKey = 'book_mark_id'`、`$incrementing = false` とする。

`updated_at` を持たないため、`UPDATED_AT = null` とする。

# 追加属性

なし

# 関数

なし

# リレーション

ほかはテーブル定義を参照

## bookMark

`book_marks` を belongsTo で取得

# 備考

ブックマーク作成時に、同じトランザクション内で作成する。
