# カラム

| カラム名   | ラベル       | 型                  | nullable | デフォルト値 | インデックス | 備考                                                       |
| ---------- | ------------ | ------------------- | -------- | ------------ | ------------ | ---------------------------------------------------------- |
| id         | ID           | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                                     |
| user_id    | ユーザーID   | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）、`book_marks_user_id_index`         |
| count      | 閲覧数       | bigint(20) UNSIGNED | false    | 0            | false        | `articles.count`と同一構造のため同様の用途（閲覧数）と推測 |
| star       | 星の数       | UNSIGNED TINYINT    | false    | 0            | false        | 0 ~ 5                                                      |
| title      | タイトル     | varchar(255)        | false    |              | false        |                                                            |
| url        | URL          | longtext            | false    |              | false        | ブックマーク先のURL                                        |
| deleted_at | 論理削除日時 | timestamp           | true     | NULL         | false        | 値が入るとゴミ箱扱い（`ゴミ箱/概要.md`参照）               |
| created_at | 作成日時     | timestamp           | true     | NULL         | false        |                                                            |
| updated_at | 更新日時     | timestamp           | true     | NULL         | false        |                                                            |

# リレーション

## user_id

`users.id` を参照する外部キー。1ユーザーは複数のブックマークを持つ。

# 制約

## user_id

外部キー制約（`book_marks_user_id_foreign`）。`ON DELETE CASCADE` のため、参照先のユーザーが削除されると、このブックマークも同時に削除される。
