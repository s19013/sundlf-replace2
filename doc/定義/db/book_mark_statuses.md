# カラム

| カラム名     | ラベル         | 型                  | nullable | デフォルト値 | インデックス | 備考                                  |
| ------------ | -------------- | ------------------- | -------- | ------------ | ------------ | ------------------------------------- |
| book_mark_id | ブックマークID | bigint(20) UNSIGNED | false    |              | true         | 主キー、外部キー（`book_marks.id`）   |
| user_id      | ユーザーID     | bigint(20) UNSIGNED | false    |              | true         | `book_marks.user_id` の写し           |
| has_tags     | タグもちフラグ | bool                | false    | false        | true         | タグを紐付けているかどうかを管理      |
| count        | 閲覧数         | bigint(20) UNSIGNED | false    | 0            | false        | 閲覧数増加APIで加算される             |
| created_at   | 作成日時       | timestamp           | true     | NULL         | false        |                                       |

ブックマーク（[`book_marks.md`](./book_marks.md)）のうち、ユーザーが直接変更しない項目を管理するテーブル。

# リレーション

## book_mark_id

`book_marks.id` を参照する外部キー。1つのブックマークに対して1行だけ存在する（1対1）。

## user_id

`book_marks.user_id` と同じ値を持つ。外部キー制約を張らない理由と、更新しない理由は [`article_statuses.md`](./article_statuses.md) の `user_id` と同じ。

# 制約

## book_mark_id

主キー制約。1つのブックマークに対して status の行が複数作られないようにする。

外部キー制約（`book_mark_statuses_book_mark_id_foreign`）。`ON DELETE CASCADE` のため、参照先のブックマークが削除されると、このテーブルの行も削除される。

# インデックス

| 名前                                        | カラム                | 用途                                                   |
| ------------------------------------------- | --------------------- | ------------------------------------------------------ |
| `book_mark_statuses_user_id_has_tags_index` | `(user_id, has_tags)` | タグなし検索で、ユーザーのタグなしの行だけを直接引く |

検索での使い方は [`article_statuses.md`](./article_statuses.md) の「検索への影響」と同じ（ブックマーク検索は未実装）。

# 備考

## 分離した理由

[`article_statuses.md`](./article_statuses.md)の「分離した理由」と同じ。

## 行の作成・削除

- ブックマーク作成時に、同じトランザクション内でこのテーブルの行も作成する。`user_id` には本体の値を設定する（`Entry::statusRowAttributes()`）。
- 論理削除（ゴミ箱）中も行は残す。ブックマークが物理削除されたときに CASCADE で削除される。

## 既存データの移行

移行時に、`book_marks` の `count` / `has_tags` の値を使って全ブックマーク分の行を作成する。その後、`book_marks` から両カラムを削除する。

`user_id` は後から追加した（#131）。手順は [`article_statuses.md`](./article_statuses.md) と同じ。

## 行が無い場合の扱い

[`article_statuses.md`](./article_statuses.md) の「行が無い場合の扱い」と同じ。

## updated_at

今は持たない。必要になったら追加する。
