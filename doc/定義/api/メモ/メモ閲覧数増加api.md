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

- メモ取得
- メモの作成者idと送信者のid確認
- 閲覧数増加処理

# エラー

## メモが取得できなかった

論理削除済み、物理削除済みなど

ステータスコード:404

```json
{
  "messages": ["メモが見つかりませんでした。"]
}
```

## メモの作成者と送信者のidが違う

ステータスコード:403

```json
{
  "messages": ["このメモは更新できません。"]
}
```

# 備考
