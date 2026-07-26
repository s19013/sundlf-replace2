# API定義レビュー

対象: `doc/定義/api/` 配下 全14ファイル

## 対象ドキュメント一覧

| ファイル                                                      | api名                     | エンドポイント                             |
| ------------------------------------------------------------- | ------------------------- | ------------------------------------------ |
| [タグ検索api.md](../定義/api/タグ/タグ検索api.md)             | tags.search               | GET `/api/tags`                            |
| [タグ更新api.md](../定義/api/タグ/タグ更新api.md)             | tags.update               | PATCH `/api/tags/{id}`                     |
| [タグ削除api.md](../定義/api/タグ/タグ削除api.md)             | tags.delete               | DELETE `/api/tags/{id}`                    |
| [タグ新規作成api.md](../定義/api/タグ/タグ新規作成api.md)     | tags.create               | POST `/api/tags/`                          |
| [全タグ取得api.md](../定義/api/タグ/全タグ取得api.md)         | tags.all                  | GET `/api/tags/all`                        |
| [メモ検索api.md](../定義/api/メモ/メモ検索api.md)             | memos.search              | GET `/api/memos/search`                    |
| [メモ取得api.md](../定義/api/メモ/メモ取得api.md)             | memos.fetch               | GET `/api/memos/{id}`                      |
| [メモ新規作成api.md](../定義/api/メモ/メモ新規作成api.md)     | memos.create              | POST `/api/memos`                          |
| [メモ更新api.md](../定義/api/メモ/メモ更新api.md)             | memos.update              | PATCH `/api/memos/{id}`                    |
| [メモ削除api.md](../定義/api/メモ/メモ削除api.md)             | memos.delete              | DELETE `/api/memos/{id}`                   |
| [メモ完全削除api.md](../定義/api/メモ/メモ完全削除api.md)     | memos.complete-deletion   | DELETE `/api/memos/{id}/completely`        |
| [メモ復元api.md](../定義/api/メモ/メモ復元api.md)             | (誤記あり、指摘1参照)     | POST `/api/memos/{id}/salvage`             |
| [メモ閲覧数増加api.md](../定義/api/メモ/メモ閲覧数増加api.md) | memos.view-count.increase | POST `/api/memos/view-count/increase/{id}` |
| [退会api.md](../定義/api/退会api.md)                          | user.cancel-membership    | POST `/api/user/cancel-membership`         |

ブックマーク関連のAPIドキュメントは存在しない（[db.md](./db.md) の指摘5、[機能.md](./機能.md) 参照）。

---

## 指摘事項

すべて対応済みのため削除

---

## 問題なしと判断した項目

- `タグ検索api.md` / `全タグ取得api.md` / `メモ検索api.md` が検索結果0件時に404を返す設計は、3ドキュメント間で一貫しており、意図的な仕様と判断。
- `api/テンプレート.md` にある「クエリパラメータ」節を省略しているファイルがあるが、テンプレート自体に「粒度は事細かくなくて良い」と明記されているため問題なし。
