# Requirements Document

## Project Description (Input)
メモ・ブックマークの updated_at の意味を「行の更新日時」(Laravel標準)に戻し、ユーザーが編集した日時を表す edited_at を新設する。並べ替え・期間絞り込み・楽観的排他制御・画面表示は edited_at を使う。あわせて has_tags を status テーブルから本体(articles / book_marks)へ戻し、本体の複合インデックス (user_id, has_tags, deleted_at, edited_at) でタグなし検索を行う。count は status テーブルに残す。背景: #127 で has_tags を status テーブルへ分離した結果、タグなし検索を速くするために status テーブルへ user_id の写しを持たせる必要が出た(#131、マージせず破棄)。一方、has_tags を本体に置くと、タグ削除で has_tags が変わったときに updated_at を更新しない特別扱いが必要になり、実際は行を更新しているのに updated_at が変わらないという違和感がある。日時の意味を分けることで両方を解消したい。API の updated_at という名前(sort=updated_at、updated_at_range_* を含む)を残すか edited_at に改名するかは未決。関連 issue: #118、#122、#127、#131、#134

## Introduction

現在、メモ・ブックマークの `updated_at` は「ユーザーがメモを編集した日時」として、更新日時順の並べ替え、期間絞り込み、楽観的排他制御、画面表示に使われている。一方で Laravel の `updated_at` は「行が更新された日時」を表すため、ユーザーの編集以外で本体の行を更新する場合（タグ削除に伴う `has_tags` の変化など）は、`updated_at` を変えない特別扱いが必要になる。

#127 ではこれを避けるために `has_tags` を status テーブルへ分離した。しかしタグなし検索の絞り込みに使う列（ユーザー・タグの有無・論理削除・更新日時）が2つのテーブルに分かれたため、検索を速くするには status テーブルへ `user_id` などの写しが必要になった（#131）。

本機能では、2つの日時の意味を分ける。

- `updated_at`：行の更新日時（Laravel 標準。どの更新でも変わる）
- `edited_at`：ユーザーが内容を編集した日時（新設）

あわせて `has_tags` を本体テーブルへ戻し、タグなし検索を本体テーブルのインデックスだけで行えるようにする。閲覧数・タグ使用回数（`count`）は引き続き status テーブルで管理する。

### 対象範囲

- 対象：メモ（`articles`）、ブックマーク（`book_marks`）、各 status テーブル、メモの API とフロントエンド、旧データ移行コマンド、関連ドキュメント
- 対象外：タグ（`tags`）の `updated_at` の意味と、タグ検索の並べ替え。ブックマークの API は未実装のため、ブックマークはデータ層（テーブル・モデル・同期処理）だけを対象にする

## Requirements

### Requirement 1: 編集日時の保持と更新
**Objective:** As a メモを使うユーザー, I want 自分が内容を編集したときだけ変わる日時を持たせたい, so that メモの「最後に手を入れた日時」を正しく把握できる

#### Acceptance Criteria
1. The Memo Service shall メモ・ブックマークごとに、行の更新日時（`updated_at`）とは別に編集日時（`edited_at`）を保持する
2. When メモまたはブックマークが作成された, the Memo Service shall 編集日時を作成日時と同じ時刻に設定する
3. When ユーザーがメモのタイトル・本文・星のいずれかを変更して保存した, the Memo Service shall 編集日時を保存した時刻に更新する
4. When ユーザーがメモに紐付けるタグを変更して保存した, the Memo Service shall タイトル・本文・星に変更が無くても編集日時を保存した時刻に更新する
5. If 保存要求の内容が保存済みの内容と同じである, the Memo Service shall 編集日時を更新しない
6. The Memo Service shall 本体の行を更新するときは、更新の理由にかかわらず行の更新日時を更新する（行の更新日時を変えないための特別扱いをしない）

### Requirement 2: 編集以外の更新では編集日時を変えない
**Objective:** As a メモを使うユーザー, I want 自分が編集していない変化で並び順や更新日時の表示が変わらないようにしたい, so that 一覧の並び順を信頼できる

#### Acceptance Criteria
1. When タグが削除され、そのタグだけが紐付いていたメモ・ブックマークのタグの有無が変化した, the Memo Service shall 該当するメモ・ブックマークの編集日時を変更しない
2. When タグ名が変更された, the Memo Service shall そのタグが紐付いているメモの編集日時を変更しない
3. When メモの閲覧数が加算された, the Memo Service shall メモの編集日時と行の更新日時のどちらも変更しない
4. When メモがゴミ箱へ移動された、またはゴミ箱から復元された, the Memo Service shall メモの編集日時を変更しない
5. When タグ削除によってメモの行が更新された, the Memo Service shall 更新日時順の検索結果でのメモの並び順を変えない

### Requirement 3: 編集日時による並べ替え・絞り込み・表示
**Objective:** As a メモを使うユーザー, I want 一覧の並べ替えや期間指定、画面の更新日時表示が自分の編集日時に基づくようにしたい, so that 編集した順にメモを探せる

#### Acceptance Criteria
1. When メモ検索で編集日時順の並べ替え（`sort=edited_at`）が指定された（並べ替えの指定が無い既定の場合を含む）, the Memo API shall 編集日時の新しい順にメモを返す
2. When メモ検索で編集日時の期間（`edited_at_range_start` / `edited_at_range_end`）が指定された, the Memo API shall 編集日時が期間内のメモだけを返す
3. The Memo API shall メモのレスポンスで、`updated_at` の代わりに `edited_at`（編集日時）を UTC の ISO 8601 形式で返す
4. If メモ検索で旧名のパラメータ（`sort=updated_at`、`updated_at_range_start`、`updated_at_range_end`）が指定された, the Memo API shall 旧名として受け付けず、バリデーションエラーを返す
5. The Frontend shall メモの更新日時として、API が返す `edited_at` をローカル日時に変換して表示する

### Requirement 4: 編集日時による楽観的排他制御
**Objective:** As a メモを使うユーザー, I want 他の画面での編集とだけ競合を判定してほしい, so that 自分が編集していない変化で保存が拒否されない

#### Acceptance Criteria
1. When メモの更新要求で指定された取得日時より後に、編集日時が更新されている, the Memo API shall 競合として保存を拒否し、保存済みの最新内容を返す
2. If 取得日時より後に行われた更新が編集以外の更新（タグ削除によるタグの有無の変化、閲覧数の加算など）だけである, the Memo API shall 競合とみなさずに保存する
3. When メモの取得要求で取得日時が指定された, the Memo API shall 更新済みかどうかの判定に編集日時を使う

### Requirement 5: タグの有無を本体テーブルで管理する
**Objective:** As a 開発者, I want タグの有無を本体テーブルで持たせたい, so that タグなし検索の絞り込み条件を1つのテーブルにまとめられる

#### Acceptance Criteria
1. The Memo Service shall タグの有無（`has_tags`）をメモ・ブックマークの本体テーブルで保持し、status テーブルには持たない
2. The Memo Service shall 閲覧数とタグ使用回数（`count`）を、引き続き status テーブルで保持する
3. When メモ・ブックマークに1つ目のタグが紐付けられた, the Memo Service shall そのメモ・ブックマークのタグの有無を「あり」にする
4. When メモ・ブックマークから最後のタグが外された, the Memo Service shall そのメモ・ブックマークのタグの有無を「なし」にする
5. When タグが削除された, the Memo Service shall そのタグ以外にタグが紐付いていないメモ・ブックマークのタグの有無を「なし」にする
6. The Memo Service shall ゴミ箱内のメモ・ブックマークも、タグの有無の同期対象にする
7. The Memo Service shall status テーブルに、タグなし検索のためだけの写し（ユーザーIDなど）を持たない

### Requirement 6: タグなし検索
**Objective:** As a メモを使うユーザー, I want タグが付いていないメモを素早く探したい, so that 整理しきれていないメモを見つけられる

#### Acceptance Criteria
1. When メモ検索でタグなし検索が指定された, the Memo API shall 検索したユーザーのメモのうち、タグの有無が「なし」のメモだけを返す
2. The Memo API shall タグなし検索の結果に、他ユーザーのメモを含めない
3. When タグなし検索とゴミ箱検索が同時に指定された, the Memo API shall ゴミ箱内でタグの有無が「なし」のメモだけを返す
4. The Memo API shall 開発環境で1ユーザー20,000件（他ユーザーを含め全100,000件）のメモがある条件で、タグなし件数の割合（3件・1%・10%・50%・90%）にかかわらず、タグなし検索の1ページ目の取得と件数取得をそれぞれ20ms以下で行う
5. The Memo API shall タグなし検索を速くするために、特定の DB バージョンや DB のセッション変数の設定を必要としない

### Requirement 7: 既存データの移行
**Objective:** As a 運用者, I want 既存データを新しい構造へ欠落なく移したい, so that デプロイ後も並び順や検索結果が変わらない

#### Acceptance Criteria
1. When マイグレーションが実行された, the Database Migration shall 既存のすべてのメモ・ブックマーク（ゴミ箱内を含む）の編集日時に、そのときの行の更新日時を設定する
2. When マイグレーションが実行された, the Database Migration shall status テーブルのタグの有無を、ゴミ箱内を含むすべてのメモ・ブックマークの本体テーブルへ写し、その後 status テーブルから削除する
3. The Database Migration shall 移行の過程で、既存の行の更新日時を変更しない
4. When マイグレーションがロールバックされた, the Database Migration shall タグの有無を status テーブルへ戻し、編集日時を削除して、移行前の構造と値に戻す
5. When マイグレーションが完了した, the Memo API shall 移行前と同じデータに対して、更新日時順の並び順とタグなし検索の結果を移行前と同じにする

### Requirement 8: 旧データ移行コマンド
**Objective:** As a 運用者, I want 旧アプリからの移行データにも編集日時とタグの有無を正しく設定したい, so that 移行直後から並べ替えとタグなし検索が正しく動く

#### Acceptance Criteria
1. When 旧データ移行コマンドがメモ・ブックマークを取り込んだ, the Legacy Import Command shall 編集日時に旧データの更新日時を設定する
2. When 旧データ移行コマンドがメモ・ブックマークを取り込んだ, the Legacy Import Command shall 旧データのタグの紐付けの有無から、本体テーブルのタグの有無を設定する

### Requirement 9: ドキュメント
**Objective:** As a 開発者, I want 2つの日時の意味の違いと、タグの有無の格納先を文書で確認したい, so that 今後の実装で使う日時を間違えない

#### Acceptance Criteria
1. The Documentation shall DB 定義（`articles`・`book_marks`・各 status テーブル）に、行の更新日時と編集日時の意味の違い、およびタグの有無の格納先を記載する
2. The Documentation shall モデル定義とメモ API 定義に、並べ替え・期間絞り込み・楽観的排他制御・表示が編集日時に基づくことを記載する
3. The Documentation shall status テーブルの分離理由を、閲覧数・タグ使用回数だけを対象とする内容に更新する

## 決定事項

1. **API の名前**：並べ替え・期間指定・レスポンスの項目名は `edited_at` に改名する（Requirement 3）。API の `updated_at` と DB の `updated_at` で意味が食い違わないようにするため。フロントエンドと生成型（`schema.d.ts`）も合わせて変更する。
2. **ゴミ箱への移動・復元時の編集日時**：変更しない（Requirement 2-4）。現状はゴミ箱への移動・復元で `updated_at` が変わるため、ゴミ箱一覧は「削除した順」に近い並びになっているが、変更後は「編集した順」に並ぶ。
3. **性能目標の値**：20ms 以下とする（Requirement 6-4）。#127 以前（本体に `has_tags`）の計測結果（1ページ目約0.2ms、件数取得0.1〜3.0ms）に余裕を持たせた値。
