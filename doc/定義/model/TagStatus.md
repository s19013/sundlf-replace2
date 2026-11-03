# テーブル 属性

[tag_statuses.md](../db/tag_statuses.md)

主キーは `tag_id`。自動採番しないため `$primaryKey = 'tag_id'`、`$incrementing = false` とする。

`updated_at` を持たないため、`UPDATED_AT = null` とする。

`$incrementing`・`UPDATED_AT`・`count` の既定値とキャストは、抽象基底クラス `StatusRow` にまとめている。`TagStatus` は `$primaryKey` と親へのリレーションだけを持つ。

# 追加属性

なし

# 関数

なし

# リレーション

ほかはテーブル定義を参照

## tag

`tags` を belongsTo で取得

# 備考

タグ作成時に、同じトランザクション内で作成する(`HasStatusRow` トレイトの created イベント)。イベントを通さずにタグを作る場合は、呼び出し側で作成すること。

count(使用回数)の増減タイミングは [Tag.md](./Tag.md) を参照。
