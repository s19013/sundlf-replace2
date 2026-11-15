# カラム

| カラム名   | ラベル         | 型                  | nullable | デフォルト値 | インデックス | 備考                                                                      |
| ---------- | -------------- | ------------------- | -------- | ------------ | ------------ | ------------------------------------------------------------------------- |
| id         | ID             | bigint(20) UNSIGNED | false    |              | true         | 主キー、AUTO_INCREMENT                                                    |
| user_id    | ユーザーID     | bigint(20) UNSIGNED | false    |              | true         | 外部キー（`users.id`）。複合インデックスの先頭（下記「インデックス」参照） |
| star       | 星の数         | UNSIGNED TINYINT    | false    | 0            | false        | 0 ~ 5                                                                     |
| title      | タイトル       | varchar(255)        | false    |              | false        |                                                                           |
| body       | 本文           | longtext            | false    |              | false        | md方式で記述される想定（`メモ/概要.md`参照）                              |
| has_tags   | タグもちフラグ | bool                | false    | false        | true         | タグを1つ以上紐付けているか。タグなし検索で使う（下記「has_tags」参照）   |
| deleted_at | 論理削除日時   | timestamp           | true     | NULL         | true         | 値が入るとゴミ箱扱い（`Memo.md`の状態定義参照）                           |
| created_at | 作成日時       | timestamp           | true     | NULL         | false        |                                                                           |
| updated_at | 行の更新日時   | timestamp           | true     | NULL         | false        | 行を更新するたびに変わる（Laravel 標準）。下記「updated_at と edited_at」参照 |
| edited_at  | 編集日時       | timestamp           | false    |              | true         | ユーザーが内容を編集した日時。下記「updated_at と edited_at」参照         |

# リレーション

## user_id

`users.id` を参照する外部キー。1ユーザーは複数のメモ(articles)を持つ。

# 制約

## user_id

外部キー制約（`articles_user_id_foreign`）。`ON DELETE CASCADE` のため、参照先のユーザーが削除されると、このメモも同時に削除される。

# インデックス

| インデックス                                           | 用途                                             |
| ------------------------------------------------------ | ------------------------------------------------ |
| `(user_id, deleted_at, edited_at)`                     | 通常一覧・ゴミ箱一覧の編集日時順。外部キーにも使う |
| `(user_id, has_tags, deleted_at, edited_at)`           | タグなし検索の絞り込みと編集日時順                 |

- 並び順の第2キーの `id` は、InnoDB がセカンダリインデックスの末尾に主キーとして持つため含めない。
- 外部キーには `user_id` を先頭に持つインデックスが必須。作り直すときは、新しいインデックスを作ってから古いものを削除する。
- ゴミ箱一覧（`deleted_at IS NOT NULL`）は範囲条件になるため、`edited_at` 順のソートに filesort が出る。

# 備考

旧システムでは`article`という名前を使っていた。
テーブル名を変えるのは面倒なのでこれをそのままメモのテーブルとする

`count`（閲覧数）は[`article_statuses.md`](./article_statuses.md)で管理する。

## updated_at と edited_at

| カラム       | 意味                         | 変わるとき                                                                                     |
| ------------ | ---------------------------- | ---------------------------------------------------------------------------------------------- |
| `updated_at` | 行の更新日時（Laravel 標準） | 行を更新したとき全部（編集、`has_tags` の同期、ゴミ箱からの復元など）                          |
| `edited_at`  | ユーザーが内容を編集した日時 | 作成時（`created_at` と同じ時刻）と、タイトル・本文・星・タグを変更して保存したときだけ        |

- 並べ替え・期間絞り込み・API のレスポンス・楽観的排他制御（`fetched_at` との比較）は、すべて `edited_at` を使う。`updated_at` は API に出さない。
- タグ削除による `has_tags` の変化、閲覧数の加算、ゴミ箱への移動・復元では、`edited_at` は変わらない。そのため、並び順が変わったり、保存が競合として扱われたりしない。
- 設定は `TracksEditedAt`（モデルのトレイト）だけが行う。生クエリでINSERTする場合（旧データ移行など）は、呼び出し側で設定する。
- 関連 issue: #134

## has_tags

- 中間テーブル（`article_tags`）から導出する値。中間モデル（`MemoTag`）のイベントと、タグ削除（`DeleteTagUsecase`）だけが書く。ゴミ箱内のメモも同期対象。
- 同期は Eloquent Builder の `update()` で行うため、`updated_at` は更新され、`edited_at` は変わらない。
- #127 で `article_statuses` に分離していたが、タグなし検索を本体のインデックスだけで行うため、#134 で本体に戻した。

## タグなし検索の性能

開発環境（MariaDB 12.0.2）で、1ユーザー20,000件（他ユーザーを含め全10万件、本文約1KB）の条件で計測した（2026-11-14、アプリが発行する SQL を7回実行した中央値）。

| タグなし件数    | 1ページ目（10件） | 件数取得（paginate） |
| --------------- | ----------------- | -------------------- |
| 3件（最古）     | 1.44ms            | 0.84ms               |
| 1%（197件）     | 0.50ms            | 0.81ms               |
| 10%（1,962件）  | 0.34ms            | 0.65ms               |
| 50%（9,812件）  | 0.38ms            | 1.87ms               |
| 90%（17,660件） | 0.33ms            | 3.14ms               |

- すべてのケースで `(user_id, has_tags, deleted_at, edited_at)` を使い、filesort は出ない。
- DB のセッション変数（`optimizer_join_limit_pref_ratio` など）の設定は不要。
