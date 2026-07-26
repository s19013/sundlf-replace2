# DB定義レビュー

対象: `doc/定義/db/` 配下 全6ファイル（テンプレート除く）

## 対象ドキュメント一覧

| ファイル                                          | テーブル名     | 概要                                          |
| ------------------------------------------------- | -------------- | --------------------------------------------- |
| [users.md](../定義/db/users.md)                   | users          | ユーザー本体                                  |
| [articles.md](../定義/db/articles.md)             | articles       | メモ本体（旧システムの`article`名を継続使用） |
| [article_tags.md](../定義/db/article_tags.md)     | article_tags   | メモ-タグ中間テーブル                         |
| [tags.md](../定義/db/tags.md)                     | tags           | タグ本体                                      |
| [book_marks.md](../定義/db/book_marks.md)         | book_marks     | ブックマーク本体                              |
| [book_mark_tags.md](../定義/db/book_mark_tags.md) | book_mark_tags | ブックマーク-タグ中間テーブル                 |

実装状況: 現時点で `backend/database/migrations/` に対応するマイグレーションが存在するのは `users` のみ。`articles` / `article_tags` / `tags` / `book_marks` / `book_mark_tags` はいずれも未実装（詳細は [フロント.md](./フロント.md) の実装状況の注記を参照）。

---

## 指摘事項

### 3. `book_marks.md` の `count` カラムの用途説明が推測ベース

- 対象: [doc/定義/db/book_marks.md](../定義/db/book_marks.md)
- 重大度: **低**

`count` カラムの備考が「`articles.count`と同一構造のため同様の用途（閲覧数）と推測」と記載されており、ドキュメント作成者自身が断定ではなく推測であることを明記している。ブックマーク機能の仕様（[機能.md](./機能.md) 指摘5参照）が丸ごと未整備なことと合わせ、要件確認が必要な状態。

**推奨対応**: ブックマーク機能の閲覧数カウント仕様を確定させたうえで、「推測」の表現を確定情報に更新する。

### 4. `book_mark_tags.md` のインデックス名が `article_tags` のまま

- 対象: [doc/定義/db/book_mark_tags.md](../定義/db/book_mark_tags.md)
- 重大度: **低**

`tag_id` カラムの備考に `article_tags_tag_id_index` と記載されているが、このテーブルは `book_mark_tags` であるため、本来は `book_mark_tags_tag_id_index` であるべき。`article_tags.md` からの複製時の修正漏れと考えられる。

**推奨対応**: インデックス名を `book_mark_tags_tag_id_index` に修正する。

### 5. ブックマーク機能のドキュメントが DB定義以外に一切存在しない

- 対象: [doc/定義/db/book_marks.md](../定義/db/book_marks.md), [doc/定義/db/book_mark_tags.md](../定義/db/book_mark_tags.md)
- 重大度: **中**

`book_marks` / `book_mark_tags` のテーブル定義は存在する一方で、`doc/定義/api/` にブックマーク関連のAPIドキュメントが1つもなく、`doc/定義/フロント/` にブックマーク関連の画面もなく、`doc/定義/機能/` にも「ブックマーク」という独立ディレクトリ/ファイルが存在しない。メモ・タグ・ゴミ箱・スターにはそれぞれ専用ドキュメントがあるのに、ブックマークだけDB定義が先行し他レイヤーの文書化が追いついていない。

**推奨対応**: ブックマーク機能をリリース対象に含めるなら、api/フロント/機能の各ドキュメントを追加する。対象外にするなら、DB定義側にもその旨（将来拡張用、現状未使用等）を明記する。

---

## 問題なしと判断した項目

- `users.md` は他テーブルからの被参照関係が明記されており、内容に矛盾は見当たらない。
- `articles.md` の「旧システムでは`article`という名前を使っていた」という命名の経緯説明は明確で、`article_tags.md` との整合性も取れている。
