# カラム

| カラム名     | ラベル         | 型                  | nullable | デフォルト値 | インデックス | 備考                                                                                                 |
| ------------ | -------------- | ------------------- | -------- | ------------ | ------------ | ---------------------------------------------------------------------------------------------------- |
| book_mark_id | ブックマークID | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`book_marks.id`）、`book_mark_tags_book_mark_id_index`                                     |
| tag_id       | タグID         | bigint(20) UNSIGNED | true     | NULL         | true         | 外部キー（`tags.id`）、`article_tags_tag_id_index`。タグが一つも紐付いていないことを表すためnullable |
| created_at   | 作成日時       | timestamp           | true     | NULL         | false        |                                                                                                      |
| updated_at   | 更新日時       | timestamp           | true     | NULL         | false        |                                                                                                      |

ブックマークとタグを紐づける中間テーブル（`タグ/概要.md`参照）。主キーの定義はなし。

# リレーション

## book_mark_id

`book_marks.id` を参照する外部キー。1つのブックマークは複数のタグと紐づく。

## tag_id

`tags.id` を参照する外部キー。1つのタグは複数のブックマークと紐づく。

# 制約

## book_mark_id

外部キー制約（`book_mark_tags_book_mark_id_foreign`）。`ON DELETE CASCADE` のため、参照先のブックマークが削除されると、この中間テーブルの行も削除される（`タグ/概要.md`にある「ブックマークが完全削除されたら中間テーブルからも削除する」仕様に対応）。

## tag_id

外部キー制約（`book_mark_tags_tag_id_foreign`）。`ON DELETE CASCADE` のため、参照先のタグが削除されると、この中間テーブルの行も削除される（同上の仕様に対応）。

## book_mark_id + tag_id (新規)

unique制約
データベース側でも、同一の組み合わせが重複登録されないように制限をかける
