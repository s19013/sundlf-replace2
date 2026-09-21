# api名

tags.search

# 概要

このAPIはタグの検索を行います。

# エンドポイント

get `/api/tags`

# 認証

必須

# クエリパラメータ

最新の詳細な型はscramble生成のOpenAPI/schema.d.ts参照

| 名前        | 型     | 必須 | デフォルト | 説明             |
| ----------- | ------ | ---- | ---------- | ---------------- |
| keywords    | string | no   | null       |                  |
| item_number | int    | no   | 10         | 表示数           |
| page        | int    | no   | 1          | ページ番号(1以上) |
| sort        | string | no   | updated_at | 何でソートするか |

# レスポンス

ステータスコード:200

```json
{
  "tags": [
    "タグモデル1",
    :
  ],
  "pagination": {
    "current_page": 2,
    "last_page": 12,
    "per_page": 10,
    "total": 115
  }
}
```

## pagination

| 名前         | 型  | 説明                                          |
| ------------ | --- | --------------------------------------------- |
| current_page | int | 現在のページ番号                              |
| last_page    | int | 最終ページ番号(1以上)                         |
| per_page     | int | 1ページあたりの表示数(`item_number`の値)      |
| total        | int | 検索条件に一致したタグの総件数(全ページの合計) |

# バリデーション

keywords:['nullable', 'string']
item_number:['nullable', 'integer', 'min:1']
page:['nullable', 'integer', 'min:1']
sort:['nullable', 'string', 'in:name,count,created_at,updated_at']

# 処理の流れ

- キーワードをand検索キーワード、マイナス検索キーワードで仕分けして配列化
- クエリに応じた検索処理
- `item_number`件ごとに区切り、`page`ページ目を取得(総件数・最終ページも算出)
- レスポンス返却

# エラー

## 検索結果なし

ステータスコード:404

```json
{
  "messages": ["見つかりませんでした。"]
}
```

## 存在しないページを指定

`page`が`last_page`を超えている場合など、そのページに表示するタグが無いときも、検索結果なしと同じ404を返す。

ステータスコード:404

```json
{
  "messages": ["見つかりませんでした。"]
}
```

# 備考

- 並び順は`sort`で指定した項目の降順。値が同じタグ同士は`id`の降順で決まる(ページ間で重複・欠落しないようにするため)。
- フロントは`pagination.current_page`と`pagination.last_page`から、ページネーション(先頭・前へ・直接リンク・プルダウン・次へ・末尾)を組み立てる。
- タグモーダルは全件取得(`tags.all`)を使うため、このAPIのページネーションは使わない。
