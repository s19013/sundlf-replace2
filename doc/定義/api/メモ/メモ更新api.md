# api名

memos.update

# 概要

このAPIは対象メモの更新を行います。

# エンドポイント

patch `/api/memos/{id}`

# 認証

必須

# パスパラメータ

最新の詳細な型はscramble生成のOpenAPI/schema.d.ts参照

| 名前 | 型     | 必須 | 説明   |
| ---- | ------ | ---- | ------ |
| id   | string | yes  | メモID |

# リクエストボディ

```json
{
  "title": "タイトル名",
  "body": "本文",
  "stars": "付与した星の数",
  "tags": ["紐付けられたタグたち"],
  "fetched_at": "最後に取得した日時"
}
```

# レスポンス

ステータスコード:200

```json
{
  "messages": ["更新しました。"]
}
```

# バリデーション

id:['required', 'integer']
title:['nullable', 'string']
body:['nullable', 'string']
stars:['nullable', 'integer', 'min:0', 'max:5']
tags:['nullable', 'array']
tags.\*:['integer']
fetched_at:['required', 'date']

`fetched_at`は必須。楽観的排他制御(競合検出)を回避して更新できないようにするため。

# 処理の流れ

- メモ取得
- メモの作成者idとログイン者のid確認
- `DB::transaction`内で以下をまとめて実行する
  - 対象メモを行ロック(`lockForUpdate`)して再取得
  - メモの`updated_at`と`fetched_at`を比較(楽観的排他制御)
  - 更新作業
  - 新しく付与されたタグはincrease,外されたタグはdecrease
- レスポンス返却

# エラー

## メモが取得できなかった

論理削除済み、物理削除済みなど
(行ロックを取得した時点で既に削除されていた場合も同じ)

ステータスコード:404

```json
{
  "messages": ["メモが見つかりませんでした。"]
}
```

## メモの作成者とログイン者のidが違う

他人のメモの存在を推測できないようにするため、403ではなく404を返す。

ステータスコード:404

```json
{
  "messages": ["このメモは更新できません。"]
}
```

## fetched_at < updated_at だった

ステータスコード:409

```json
{
  "messages": [
    "保存できませんでした。",
    "他の画面で記事が更新されています。",
    "競合状態を修正してください"
  ],
  "saved": "メモモデル"
}
```

# 備考

- `title`・`body`・`stars`は`null`(未送信の場合を含む)の場合は更新されない。
- `tags`キーが送信された場合のみタグを同期する。未送信の場合はタグを変更しない。空配列`[]`が送信された場合は全てのタグが外される。
- ログイン者が所有していないタグのidが指定された場合は無視される。
