# テーブル 属性

[article_statuses.md](../db/article_statuses.md)

テーブル名がモデル名と一致しないため、`$table = 'article_statuses'` を明示する。

主キーは `article_id`。自動採番しないため `$primaryKey = 'article_id'`、`$incrementing = false` とする。

`updated_at` を持たないため、`UPDATED_AT = null` とする。

# 追加属性

なし

# 関数

なし

# リレーション

ほかはテーブル定義を参照

## memo

`articles` を belongsTo で取得

# 備考

メモ作成時に、同じトランザクション内で作成する。
