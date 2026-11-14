# テーブル 属性

[book_mark_tags](../db/book_mark_tags.md)

# 追加属性

なし

# 関数

なし

# リレーション

多対多

# 備考

このモデルは中間テーブルのモデル

## has_tags の同期

`SyncsEntryHasTags` トレイト(`backend/app/Models/Concerns/SyncsEntryHasTags.php`)で、紐付けの増減に合わせて親のブックマークの `book_marks.has_tags` を同期する。

- `created`(attach / sync / toggle での追加): 親の `has_tags` が false なら true にする
- `deleted`(detach / sync / toggle での解除): 親にタグが残っていなければ false にする
- 親は `withTrashed()` で引くため、ゴミ箱内も同期対象
- Eloquent Builder の `update()` で書くため、親の `updated_at` は更新され、`edited_at` は変わらない
- ON DELETE CASCADE や生クエリで中間テーブルを変更した場合はイベントが発火しないため、呼び出し側で同期する(タグ削除は `DeleteTagUsecase` が削除前に同期する)
