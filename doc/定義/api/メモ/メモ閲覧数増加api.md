# api名

memos.view-count.increase

# 概要

このAPIは対象のメモの閲覧数を1増やします。

# エンドポイント

post `/api/memos/view-count/increase/{id}`

# 認証

必須

# パスパラメータ

最新の詳細な型はscramble生成のOpenAPI/schema.d.ts参照

| 名前 | 型     | 必須 | 説明   |
| ---- | ------ | ---- | ------ |
| id   | string | yes  | メモID |

# レスポンス

ステータスコード:200

# バリデーション

id:['required']

# 処理の流れ

- 閲覧数増加処理

# エラー

## 対が見つからない

ステータスコード:404

# 備考
