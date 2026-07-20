# api名

user.cancel-membership

# 概要

このAPIは退会処理を行います。

# エンドポイント

post `/api/user/cancel-membership`

# 認証

必須

# レスポンス

ステータスコード:200

```json
{
  "messages": ["退会しました。"]
}
```

# 処理の流れ

- ユーザーテーブルからデータを物理削除
- ユーザーが今まで作成したデータを物理削除
- レスポンス返却

# エラー

# 備考
