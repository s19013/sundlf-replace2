# カラム

| カラム名           | ラベル               | 型                   | nullable | デフォルト値 | インデックス | 備考                                                             |
| ------------------ | -------------------- | -------------------- | -------- | ------------ | ------------- | ------------------------------------------------------------------ |
| id                  | ID                    | bigint(20) UNSIGNED   | false    |              | true          | 主キー、AUTO_INCREMENT                                            |
| name                | ユーザー名            | varchar(255)          | false    |              | false         |                                                                     |
| email               | メールアドレス        | varchar(255)          | false    |              | true          | UNIQUE制約あり（`users_email_unique`）                             |
| email_verified_at   | メール認証日時        | timestamp             | true     | NULL         | false         |                                                                     |
| password            | パスワード            | varchar(255)          | false    |              | false         | ハッシュ化された値を保持                                          |
| remember_token      | 自動ログイン用トークン | varchar(100)          | true     | NULL         | false         | Laravelの「ログイン状態を保持する」機能用トークン                 |
| created_at          | 作成日時              | timestamp             | true     | NULL         | false         |                                                                     |
| updated_at          | 更新日時              | timestamp             | true     | NULL         | false         |                                                                     |
| logined_at          | 最終ログイン日時       | timestamp             | true     | NULL         | false         |                                                                     |

# リレーション

## id

`articles.user_id`、`tags.user_id`、`book_marks.user_id` から参照される（1ユーザーは複数のメモ・タグ・ブックマークを持つ）。

# 制約

## email

UNIQUE制約（`users_email_unique`）。メールアドレスの重複登録不可。
