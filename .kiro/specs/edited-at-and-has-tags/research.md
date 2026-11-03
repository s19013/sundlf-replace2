# Research & Design Decisions

## Summary
- **Feature**: `edited-at-and-has-tags`
- **Discovery Scope**: Extension（既存のメモ・ブックマーク・status テーブルの構成変更。新しい外部依存なし）
- **Key Findings**:
  - Eloquent の保存処理では、タイムスタンプの更新（`updateTimestamps()`）を経由するのは `save()` / `touch()` だけで、クエリビルダーの `update()` と SoftDeletes の論理削除は経由しない。そのため、`edited_at` をタイムスタンプ更新の拡張として設定すると、ゴミ箱への移動・`has_tags` の同期では `edited_at` が変わらない。
  - `has_tags` の同期を本体モデルの Eloquent Builder の `update()` に変えると、`updated_at` が自動で付与される（Requirement 1.6 をそのまま満たす）。
  - 既存のマイグレーションテスト（`CopyCountAndHasTagsToStatusTablesMigrationTest` / `RecalculateHasTagsMigrationTest`）は、setUp で `2026_11_03_043655` の `down()` を直接呼び、本体に `has_tags` を戻してから検証している。新しいマイグレーションで本体に `has_tags` を追加すると、カラムが重複して失敗する。

## Research Log

### 保存処理とタイムスタンプ更新の経路
- **Context**: `edited_at` を「ユーザーの編集時だけ」更新する仕組みを、Usecase ごとの書き忘れが起きない形で置きたい。
- **Sources Consulted**: Laravel 12 の `Illuminate\Database\Eloquent\Model::save()` / `performUpdate()` / `performInsert()`、`Concerns\HasTimestamps::updateTimestamps()` / `touch()`、`SoftDeletes::runSoftDelete()` / `restore()`、`Eloquent\Builder::update()`（`addUpdatedAtColumn()`）
- **Findings**:
  - `save()` は、既存行で `isDirty()` が偽なら `performUpdate()` を呼ばない。つまり変更が無ければタイムスタンプも書かない。
  - `performInsert()` / `performUpdate()` は、`creating` / `updating` イベントの後に `updateTimestamps()` を呼ぶ。`updateTimestamps()` は1つの `$time` で `updated_at`（と新規時の `created_at`）を設定する。
  - `SoftDeletes::runSoftDelete()` は、クエリで `deleted_at` と `updated_at` を直接更新する。`updateTimestamps()` は経由しない。
  - `SoftDeletes::restore()` は `save()` を呼ぶ。変更されるのは `deleted_at` だけ。
  - `Eloquent\Builder::update()` は `updated_at` を自動で付与する。`toBase()` を経由すると付与しない。
- **Implications**: `updateTimestamps()` を拡張し、「編集対象の属性が変更されている、または編集済みの印がある」ときだけ、同じ `$time` を `edited_at` にも設定する。これで次の要件を満たせる。
  - 作成時は `created_at` と同時刻（1.2）
  - 編集時は `updated_at` と同時刻（1.3、1.4）
  - 変更なしでは書かない（1.5）
  - ゴミ箱への移動・復元とタグ同期では変えない（2.1、2.4）

### タグの付け替えだけの保存
- **Context**: 現在の `UpdateMemoUsecase` は、タグだけを変えたときに `touch()` で `updated_at` を更新している。
- **Findings**: タグの付け替えは中間テーブルへの書き込みで、本体の属性は dirty にならない。そのため `save()` だけでは `edited_at` が更新されない。
- **Implications**: 「編集済み」として印を付ける公開メソッド（`markAsEdited()`）を用意する。このメソッドはモデルを dirty にして `save()` を確実に `performUpdate()` へ進め、`touch()` を置き換える。

### タグなし検索とインデックス
- **Context**: 要件 6.4 は、1ユーザー20,000件の条件で20ms以下。
- **Sources Consulted**: `.research-docs/entry-columns-count-star-has_tags.md`、`.research-docs/entry-columns-count-star-has_tags.branch-feature-issue127.md`（#118・#127・#131 の計測と EXPLAIN）
- **Findings**:
  - 本体の `(user_id, has_tags, deleted_at, updated_at)` を使ったときの計測（#127 以前、#131 での再計測）は、1ページ目約0.2ms、件数取得0.1〜3.0ms。filesort なし。
  - `has_tags` を先頭側に含む複合インデックスでは、タグなし検索以外の一覧（`user_id = ? AND deleted_at IS NULL ORDER BY updated_at`）で filesort を避けられない。#127 では `(user_id, deleted_at, updated_at)` に作り直していた。
  - InnoDB はセカンダリインデックスの末尾に主キーを持つため、第2ソートキーの `id` はインデックスに含めなくてよい。
  - `user_id` の外部キーには、`user_id` を先頭に持つインデックスが必須。新しいインデックスを作ってから古いものを消す必要がある。
- **Implications**: 2つの複合インデックスを持つ。
  - 通常の一覧用：`(user_id, deleted_at, edited_at)`
  - タグなし検索用：`(user_id, has_tags, deleted_at, edited_at)`
  
  `optimizer_join_limit_pref_ratio` などのセッション変数は不要になる（6.5）。

### 影響範囲の調査（コード）
- **Findings**:
  - `updated_at` を「編集日時」として使っている箇所：`SearchMemosUsecase`（既定の並べ替え、期間絞り込み）、`SearchMemoRequest`（`sort` の許可値、期間パラメータ）、`DetectsStaleData::hasBeenUpdatedSinceRetrieval()`（`UpdateMemoUsecase` / `FetchMemoUsecase`）、`MemoResource`、`UpdateMemoUsecase` の `touch()`
  - `has_tags` の読み書き：`SyncsEntryHasTags` + `MemoTag` / `BookMarkTag`、`DeleteTagUsecase`、`SearchMemosUsecase`、`EntryStatus`（既定値・キャスト・`tags()`）、`ImportLegacyDataCommand`（status への insert）
  - フロントエンドには、メモの画面と `updated_at` の参照が無い（`frontend/src/entities` は `auth` / `counter` だけ）。影響は生成ファイル（`schema.d.ts` / ziggy）の再生成だけ。
  - ブックマークの Usecase・API は未実装。
  - `TagResource` / `SearchTagsUsecase` の `updated_at` はタグの日時で、対象外。

## Architecture Pattern Evaluation

| Option | Description | Strengths | Risks / Limitations | Notes |
|--------|-------------|-----------|---------------------|-------|
| A. モデル層で `edited_at` を自動設定 | `updateTimestamps()` を拡張し、編集対象属性の変更か `markAsEdited()` で設定する | Usecase の書き忘れが起きない。`created_at` / `updated_at` と同時刻になる | 暗黙の振る舞いになる。編集対象属性の一覧の保守が必要 | 採用。`has_tags` 同期をモデル層に置いた方針（#118）と同じ |
| B. Usecase で明示的に設定 | Create / Update の Usecase で `edited_at = now()` | 振る舞いが明示的 | 新しい Usecase（ブックマーク API など）で書き忘れやすい | 不採用 |
| C. `saving` イベントで設定 | 編集対象属性が dirty なら `edited_at` を設定する | 実装が単純 | `updated_at` とは別の時刻を取るので、ミリ秒単位でずれうる。dirty でない保存では発火しない | 不採用 |

## Design Decisions

### Decision: `edited_at` の設定方式
- **Context**: 1.2〜1.5、2.1〜2.4
- **Alternatives Considered**: 上表の A / B / C
- **Selected Approach**: A。`Entry` に新しいトレイト `TracksEditedAt` を追加する。各モデルは `editableAttributes()` で編集対象の属性を宣言する（Memo：`title` / `body` / `star`、BookMark：`title` / `url` / `star`）。
- **Rationale**: 書き忘れを防げる。タイムスタンプ3つの時刻がそろう。ゴミ箱への移動・復元、`has_tags` の同期、閲覧数の加算のどれも、仕組み上 `edited_at` に触れない。
- **Trade-offs**: 編集対象の属性を追加するときは、`editableAttributes()` の更新が必要になる。
- **Follow-up**: `restore()` で `edited_at` が変わらないこと、変更なしの保存で何も書かれないことをテストで確認する。

### Decision: `has_tags` 同期の書き込み先
- **Context**: 5.3〜5.6、1.6
- **Selected Approach**: `SyncsEntryHasTags` の抽象メソッドを「親エントリー（本体）1件のクエリ」に戻す（`withTrashed()` 付き）。Eloquent Builder の `update()` で書くので、`updated_at` は自動で更新される。`toBase()` は使わない。
- **Rationale**: #118 時点の仕組みから、`updated_at` を変えないための特別扱いだけを取り除く。
- **Trade-offs**: タグの付け替え時は、本体への UPDATE が「`has_tags` の同期」と「編集の保存」の2回になる（#118 と同じ）。

### Decision: `EntryStatus` を廃止する
- **Context**: 5.1、5.2、5.7
- **Selected Approach**: `has_tags` が無くなると、`EntryStatus` の責務（`has_tags` の既定値・キャスト・`tags()`・`tagPivotTable()`）が残らない。`MemoStatus` / `BookMarkStatus` は `StatusRow` を直接継承する。
- **Rationale**: 責務の無い中間クラスを残さない。`TagStatus` と同じ形になる。

### Decision: インデックスを2本にする
- **Context**: 3.1、6.4
- **Selected Approach**:
  - `(user_id, deleted_at, edited_at)`：通常の一覧。既定の並べ替え
  - `(user_id, has_tags, deleted_at, edited_at)`：タグなし検索
  
  旧 `(user_id, deleted_at, updated_at)` は削除する。
- **Trade-offs**: 書き込み時のインデックス更新が1本増える。個人用メモアプリの書き込み頻度では無視できる。

### Decision: マイグレーションを3段階に分ける
- **Context**: 7.1〜7.5
- **Selected Approach**: #127 と同じく DDL / DML / DDL に分ける。
  1. カラム追加（nullable）
  2. 値のコピー
  3. NOT NULL 化・インデックスの作り直し・status からの `has_tags` 削除
- **Rationale**: スキーマ変更とデータ操作を分けると、失敗時の切り分けとロールバックが容易になる。
- **Follow-up**: 既存の2つのマイグレーションテストは、setUp で新しいマイグレーションを逆順に `down()` してから、従来の `down()` を呼ぶ形に直す（テストの削除はしない）。

### Decision: `has_tags` の移行元
- **Context**: 7.2、7.5
- **Selected Approach**: 中間テーブルから再計算せず、status テーブルの値を写す。status 行が無い本体は `false`（既定値）にする。
- **Rationale**: 現在のタグなし検索は「`has_tags = true` の status 行が無い」で判定しており、status 行が無い本体はタグなしとして扱われる。移行前後で検索結果を同じにする（7.5）。

## Risks & Mitigations
- 既存のマイグレーションテストが、カラムの重複で失敗する。対策として、setUp のロールバック手順を新しいマイグレーションから順に戻す形へ直す。
- 古いインデックスを先に消すと、外部キーが使うインデックスが無くなり、MariaDB がエラーを出す。対策として、新しいインデックスを作ってから古いものを消す。
- テストで `updated_at` を指定して並び順を作っている箇所が、`edited_at` 基準では意図どおりに並ばない。対策として、並び順・期間・排他制御のテストは `edited_at` を指定する形に直す。
- `markAsEdited()` を呼び忘れると、タグだけの変更が編集日時に反映されない。対策として、`UpdateMemoUsecase` のテスト（タグだけの付け替えで `edited_at` が変わる）で担保する。

## References
- `.research-docs/entry-columns-count-star-has_tags.md` — #118 の `has_tags` 同期とインデックスの判断
- `.research-docs/entry-columns-count-star-has_tags.branch-feature-issue127.md` — #127 / #131 の構成・計測結果
- `doc/定義/db/article_statuses.md` — status テーブルの分離理由
