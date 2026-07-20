# カラム

| カラム名   | ラベル       | 型                  | nullable | デフォルト値 | インデックス | 備考                                                    |
| ---------- | ------------ | ------------------- | -------- | ------------ | ------------ | ------------------------------------------------------- |
| id         | ID           | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                                  |
| user_id    | ユーザーID   | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）、`articles_user_id_index`        |
| count      | 閲覧数       | bigint(20) UNSIGNED | false    | 0            | false        | 閲覧数増加APIで加算される（`メモ閲覧数増加api.md`参照） |
| star       | 星の数       | UNSIGNED TINYINT    | false    | 0            | false        | 0 ~ 5                                                   |
| title      | タイトル     | varchar(255)        | false    |              | false        |                                                         |
| body       | 本文         | longtext            | false    |              | false        | md方式で記述される想定（`メモ/概要.md`参照）            |
| deleted_at | 論理削除日時 | timestamp           | true     | NULL         | false        | 値が入るとゴミ箱扱い（`Memo.md`の状態定義参照）         |
| created_at | 作成日時     | timestamp           | true     | NULL         | false        |                                                         |
| updated_at | 更新日時     | timestamp           | true     | NULL         | false        |                                                         |

# リレーション

## user_id

`users.id` を参照する外部キー。1ユーザーは複数のメモ(articles)を持つ。

# 制約

## user_id

外部キー制約（`articles_user_id_foreign`）。`ON DELETE CASCADE` のため、参照先のユーザーが削除されると、このメモも同時に削除される。

# 備考

旧システムでは`article`という名前を使っていた。
テーブル名を変えるのは面倒なのでこれをそのままメモのテーブルとする
