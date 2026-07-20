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
| item_number            | int    | yes  | 10         | 表示数                             |
| sort                   | string | yes  | updated_at | 何でソートするか                   |
| target                 | string | yes  | title      | 何を対象に検索するか               |
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

# 処理の流れ

- キーワードをand検索キーワード、マイナス検索キーワードで仕分けして配列化
- クエリに応じた検索処理
- レスポンス返却

# エラー

## no hit

ステータスコード:404

```json
{
  "messages": ["見つかりませんでした。"]
}
```

# 備考
