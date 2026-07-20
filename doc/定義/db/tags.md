# カラム

| カラム名   | ラベル       | 型                  | nullable | デフォルト値 | インデックス | 備考                                         |
| ---------- | ------------ | ------------------- | -------- | ------------ | ------------ | -------------------------------------------- |
| id         | ID           | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                       |
| name       | タグ名       | varchar(255)        | false    |              | false        |                                              |
| user_id    | ユーザーID   | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）、`tags_user_id_index` |
| count      | 使用回数     | bigint(20) UNSIGNED | false    | 0            | false        | 紐づくメモ・ブックマークの数                 |
| deleted_at | 論理削除日時 | timestamp           | true     | NULL         | false        |                                              |
| created_at | 作成日時     | timestamp           | true     | NULL         | false        |                                              |
| updated_at | 更新日時     | timestamp           | true     | NULL         | false        |                                              |

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
