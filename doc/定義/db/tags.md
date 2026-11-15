# カラム

| カラム名   | ラベル       | 型                  | nullable | デフォルト値 | インデックス | 備考                                         |
| ---------- | ------------ | ------------------- | -------- | ------------ | ------------ | -------------------------------------------- |
| id         | ID           | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                       |
| name       | タグ名       | varchar(255)        | false    |              | false        |                                              |
| user_id    | ユーザーID   | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）、`tags_user_id_index` |
| created_at | 作成日時     | timestamp           | true     | NULL         | false        |                                              |
| updated_at | 行の更新日時 | timestamp           | true     | NULL         | false        | 行を更新するたびに変わる（Laravel 標準）     |
| edited_at  | 編集日時     | timestamp           | false    |              | false        | ユーザーがタグ名を編集した日時               |

# リレーション

## user_id

`users.id` を参照する外部キー。1ユーザーは複数のタグを持つ。

## id

中間テーブル `article_tags.tag_id` から参照される。タグはメモ・ブックマークと中間テーブル経由で紐づく（`タグ/概要.md`参照）。

# 制約

## user_id

外部キー制約（`tags_user_id_foreign`）。`ON DELETE CASCADE` のため、参照先のユーザーが削除されると、このタグも同時に削除される。

## user_id + name (新規)

ユニーク制約
ユーザー単位での重複禁止をdbでも制御

# 備考

`count`（使用回数）は[`tag_statuses.md`](./tag_statuses.md)で管理する。

## updated_at と edited_at

メモ（[`articles.md`](./articles.md)）と同じ考え方で、2つの日時を分けている。

- `edited_at` が変わるのは、作成時（`created_at` と同じ時刻）と、タグ名を変更して保存したときだけ。同じ名前で保存しても変わらない。
- メモへの紐付けの追加・解除、メモのゴミ箱への移動・復元による使用回数の増減は `tag_statuses` への更新なので、`edited_at` も `updated_at` も変わらない。
- タグ検索の並べ替え（`sort=edited_at`）と API のレスポンスは `edited_at` を使う。`updated_at` は API に出さない。
- タグの件数は少ない（1,000件の `sort=count` で1.7ms）ため、`edited_at` 用のインデックスは作らない。

削除について
論理削除は行わず、物理削除

特段問題なければ削除予定
