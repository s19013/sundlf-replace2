# api名

memos.search

# 概要

このAPIはメモの検索を行います。

# エンドポイント

get /api/memos/search

# 認証

必須

# クエリパラメータ

最新の詳細な型はscramble生成のOpenAPI/schema.d.ts参照

| 名前                   | 型     | 必須 | デフォルト | 説明                               |
| ---------------------- | ------ | ---- | ---------- | ---------------------------------- |
| keyword                | string | no   | null       |                                    |
| item_number            | int    | no   | 10         | 表示数(1〜100)                     |
| sort                   | string | no   | updated_at | 何でソートするか                   |
| target                 | string | no   | title      | 何を対象に検索するか               |
| is_tag_not_attached    | bool   | no   | null       | タグ無しのデータを検索するか       |
| exact_match_tags       | array  | no   | null       | 紐付けする完全一致タグのid         |
| partial_match_tags     | array  | no   | null       | 紐付けする部分一致タグのid         |
| exclusion_match_tags   | array  | no   | null       | 紐付けする除外タグのid             |
| stars                  | int    | no   | null       | メモにつけた星の数                 |
| created_at_range_start | string | no   | null       | "/" 区切りで渡される               |
| created_at_range_end   | string | no   | null       | "/" 区切りで渡される               |
| updated_at_range_start | string | no   | null       | "/" 区切りで渡される               |
| updated_at_range_end   | string | no   | null       | "/" 区切りで渡される               |
| is_in_trashbox         | bool   | no   | null       | trueなら論理削除のデータのみを対象 |

# レスポンス

ステータスコード:200

```json
{
  "memos": [
    "めもモデル1",
    :
  ]
}
```

# バリデーション

keyword:['nullable', 'string']
item_number:['nullable', 'integer', 'min:1', 'max:100']
sort:['nullable', 'string', 'in:updated_at,created_at,title,count,random']
target:['nullable', 'string', 'in:title,body,both']
is_tag_not_attached:['nullable', 'boolean']
exact_match_tags:['nullable', 'array']
exact_match_tags.\*:['integer']
partial_match_tags:['nullable', 'array']
partial_match_tags.\*:['integer']
exclusion_match_tags:['nullable', 'array']
exclusion_match_tags.\*:['integer']
stars:['nullable', 'integer', 'min:0', 'max:5']
created_at_range_start:['nullable', 'date']
created_at_range_end:['nullable', 'date']
updated_at_range_start:['nullable', 'date']
updated_at_range_end:['nullable', 'date']
is_in_trashbox:['nullable', 'boolean']

# 処理の流れ

- キーワードをand検索キーワード、マイナス検索キーワードで仕分けして配列化
- クエリに応じた検索処理
- レスポンス返却

# エラー

## 検索結果なし

ステータスコード:404

```json
{
  "messages": ["見つかりませんでした。"]
}
```

# 備考
