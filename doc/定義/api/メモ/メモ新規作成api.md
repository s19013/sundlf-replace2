# api名

memos.create

# 概要

このAPIはメモの新規作成を行います。

# エンドポイント

post `/api/memos`

# 認証

必須

# リクエストボディ

```json
{
  "title": "タイトル名",
  "body": "本文",
  "stars": "付与した星の数",
  "tags": ["紐付けられたタグたち"]
}
```

# レスポンス

ステータスコード:200

```json
{
  "id": "新規作成したメモのid"
}
```

# バリデーション

title:['nullable', 'string']
body:['nullable', 'string']
stars:['nullable', 'integer', 'min:0', 'max:5']
tags:['nullable', 'array']
tags.\*:['integer']

# 処理の流れ

- `DB::transaction`内で以下をまとめて実行する(タグ同期に失敗した場合に、メモだけ作成された状態が残らないようにするため)
  - 作成処理
  - 新しく付与されたタグはincrease
- レスポンス返却

# エラー

# 備考

- ログイン者が所有していないタグのidが指定された場合は無視され、紐付かない。
