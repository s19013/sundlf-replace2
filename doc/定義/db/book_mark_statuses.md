# カラム

| カラム名     | ラベル         | 型                  | nullable | デフォルト値 | インデックス | 備考                                |
| ------------ | -------------- | ------------------- | -------- | ------------ | ------------ | ----------------------------------- |
| book_mark_id | ブックマークID | bigint(20) UNSIGNED | false    |              | true         | 主キー、外部キー（`book_marks.id`） |
| has_tags     | タグもちフラグ | bool                | false    | false        | false        | タグを紐付けているかどうかを管理    |
| count        | 閲覧数         | bigint(20) UNSIGNED | false    | 0            | false        | 閲覧数増加APIで加算される           |
| created_at   | 作成日時       | timestamp           | true     | NULL         | false        |                                     |

ブックマーク（[`book_marks.md`](./book_marks.md)）のうち、ユーザーが直接変更しない項目を管理するテーブル。

# リレーション

## book_mark_id

`book_marks.id` を参照する外部キー。1つのブックマークに対して1行だけ存在する（1対1）。

# 制約

## book_mark_id

主キー制約。1つのブックマークに対して status の行が複数作られないようにする。

外部キー制約（`book_mark_statuses_book_mark_id_foreign`）。`ON DELETE CASCADE` のため、参照先のブックマークが削除されると、このテーブルの行も削除される。

# 備考

## 分離した理由

[`article_statuses.md`](./article_statuses.md)の「分離した理由」と同じ。

## 行の作成・削除

- ブックマーク作成時に、同じトランザクション内でこのテーブルの行も作成する。
- 論理削除（ゴミ箱）中も行は残す。ブックマークが物理削除されたときに CASCADE で削除される。

## 既存データの移行

移行時に、`book_marks` の `count` / `has_tags` の値を使って全ブックマーク分の行を作成する。その後、`book_marks` から両カラムを削除する。

## updated_at

今は持たない。必要になったら追加する。
