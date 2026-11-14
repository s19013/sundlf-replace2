# カラム

| カラム名   | ラベル         | 型                  | nullable | デフォルト値 | インデックス | 備考                                                                      |
| ---------- | -------------- | ------------------- | -------- | ------------ | ------------ | ------------------------------------------------------------------------- |
| id         | ID             | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                                                    |
| user_id    | ユーザーID     | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）。複合インデックスの先頭（下記「インデックス」参照） |
| star       | 星の数         | UNSIGNED TINYINT    | false    | 0            | false        | 0 ~ 5                                                                     |
| title      | タイトル       | varchar(255)        | false    |              | false        |                                                                           |
| url        | URL            | longtext            | false    |              | false        | ブックマーク先のURL                                                       |
| has_tags   | タグもちフラグ | bool                | false    | false        | true         | タグを1つ以上紐付けているか                                               |
| deleted_at | 論理削除日時   | timestamp           | true     | NULL         | true         | 値が入るとゴミ箱扱い（`ゴミ箱/概要.md`参照）                              |
| created_at | 作成日時       | timestamp           | true     | NULL         | false        |                                                                           |
| updated_at | 行の更新日時   | timestamp           | true     | NULL         | false        | 行を更新するたびに変わる（Laravel 標準）                                  |
| edited_at  | 編集日時       | timestamp           | false    |              | true         | ユーザーが内容を編集した日時                                              |

# リレーション

## user_id

`users.id` を参照する外部キー。1ユーザーは複数のブックマークを持つ。

# 制約

## user_id

外部キー制約（`book_marks_user_id_foreign`）。`ON DELETE CASCADE` のため、参照先のユーザーが削除されると、このブックマークも同時に削除される。

# インデックス

| インデックス                                 | 用途                                               |
| -------------------------------------------- | -------------------------------------------------- |
| `(user_id, deleted_at, edited_at)`           | 通常一覧・ゴミ箱一覧の編集日時順。外部キーにも使う |
| `(user_id, has_tags, deleted_at, edited_at)` | タグなし検索の絞り込みと編集日時順                 |

# 備考

`count`（閲覧数）は[`book_mark_statuses.md`](./book_mark_statuses.md)で管理する。

`updated_at` と `edited_at` の違い、`has_tags` の同期方法は、[`articles.md`](./articles.md)と同じ。編集とみなす属性は、タイトル・URL・星・タグ。

ブックマークの API はまだ無いため、現時点ではデータ層（テーブル・モデル・同期処理）だけが対象。
