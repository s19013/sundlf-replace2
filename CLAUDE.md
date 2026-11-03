# AI-DLC and Spec-Driven Development

Kiro-style Spec Driven Development implementation on AI-DLC (AI Development Life Cycle)

## Project Context

### Paths

- Steering: `.kiro/steering/`
- Specs: `.kiro/specs/`

### Steering vs Specification

**Steering** (`.kiro/steering/`) - Guide AI with project-wide rules and context
**Specs** (`.kiro/specs/`) - Formalize development process for individual features

### Active Specifications

- Check `.kiro/specs/` for active specifications
- Use `/kiro:spec-status [feature-name]` to check progress

## Development Guidelines

- Think in English, generate responses in Japanese. All Markdown content written to project files (e.g., requirements.md, design.md, tasks.md, research.md, validation reports) MUST be written in the target language configured for this specification (see spec.json.language).

## Minimal Workflow

- Phase 0 (optional): `/kiro:steering`, `/kiro:steering-custom`
- Phase 1 (Specification):
  - `/kiro:spec-init "description"`
  - `/kiro:spec-requirements {feature}`
  - `/kiro:validate-gap {feature}` (optional: for existing codebase)
  - `/kiro:spec-design {feature} [-y]`
  - `/kiro:validate-design {feature}` (optional: design review)
  - `/kiro:spec-tasks {feature} [-y]`
- Phase 2 (Implementation): `/kiro:spec-impl {feature} [tasks]`
  - `/kiro:validate-impl {feature}` (optional: after implementation)
- Progress check: `/kiro:spec-status {feature}` (use anytime)

## Development Rules

- 3-phase approval workflow: Requirements → Design → Tasks → Implementation
- Human review required each phase; use `-y` only for intentional fast-track
- Keep steering current and verify alignment with `/kiro:spec-status`
- Follow the user's instructions precisely, and within that scope act autonomously: gather the necessary context and complete the requested work end-to-end in this run, asking questions only when essential information is missing or the instructions are critically ambiguous.

## Steering Configuration

- Load entire `.kiro/steering/` as project memory
- Default files: `product.md`, `tech.md`, `structure.md`
- Custom files are supported (managed via `/kiro:steering-custom`)

## バックエンドのルール

@backend/CLAUDE.md に書いてある

## フロントエンドのルール

@frontend/CLAUDE.md に書いてある

## 基本的な実装順序

- バックエンドを実装
- ※1 以下のコマンドを実行してフロントで必要な情報を出力する
  - `mise generate:ziggy`
  - `mise generate:openapi`
- フロントエンドを実装

※1 ルートや API レスポンススキーマを変更した際に実行。
※1 生成ファイルは `frontend/src/lib/api/generated/` に出力されます

## このプロジェクトでのルール

- 回答は日本語で行う
- 変更する前に、何を変更するか簡潔に説明してください
- 既存のコードスタイルに合わせる
- 勝手に大規模リファクタリングしない
- 既存の設計を大きく変える場合は、先に理由を説明してください
- コマンドを実行する前に目的を日本語で説明する
- `.tmp`ディレクトリは人間が考えをまとめるためのメモなどの書きなぐりを保存する場所、ふるいドキュメントを保管する場所、見ても良いが特段利益はないため、見なくて良い。
- ロールバックなどがしやすいように段階ごとにコミットする

## 言語設定

- 常に日本語で会話する
- コメントも日本語で記述する
- エラーメッセージの説明も日本語で行う
- ドキュメントも日本語で生成する

## 調査結果の記録と再利用

### 目的

同じコードや仕様を繰り返し調査することによる時間・トークン消費を削減するため、調査によって判明した再利用可能な知見を `.research-docs/` 配下にMarkdown形式で記録する。

### 調査前の確認

コードや仕様の調査を始める前に、`.research-docs/` 配下に関連する調査結果がないか確認する。

- 関連する記録がある場合は、その内容を起点として調査する。最初から同じ調査を繰り返さない。
- 記録の内容が現在のコードにも当てはまると判断できる場合は、既存の調査結果を再利用する。
- 変更や不足が疑われる部分のみ追加で調査する。
- 記録と現在のコードが矛盾する場合は、現在のコードを優先する。
- 関連する記録が見つからない場合は、通常どおり調査する。

過去の調査結果が存在するという理由だけで、無条件に正しいと判断しない。一方で、ブランチやHEADのコミットが異なるという理由だけで、全体を再調査しない。

### 調査結果の記録

調査によって新しい再利用可能な知見が判明した場合は、関連する既存ドキュメントを更新するか、新規作成する。

調査結果が古くなっていた場合は、必要に応じて修正、統合、削除する。

記録する内容の例：

- データや処理の流れ
- クラス、関数、テーブル、APIなどの役割
- どの機能から参照・使用されているか
- 現在使用されていないと判断した処理やテーブル
- 調査によって判明した仕様や制約
- 判断の根拠となったファイルパスやシンボル
- 未確認事項、推測、調査上の注意点

以下の情報は原則として記録しない。

- 一時的な作業状況
- 今回の変更内容だけに関係する情報
- コードを見ればすぐ分かる単純な情報
- 他の調査ドキュメントと重複する情報

調査過程を逐一記録するのではなく、**次回の調査を省略・短縮できる結論と根拠**を優先する。

事実、推測、未確認事項を明確に区別する。

### 記録ファイルの管理

ファイル名は調査対象や機能が分かる名前とし、関連する内容はできる限り同じファイルにまとめる。

各ファイルの先頭に以下を記載する。

```yaml
---
branch: 調査時のブランチ名
commit: 調査内容を確認したHEADのコミットID
created: YYYY-MM-DD HH:mm
updated: YYYY-MM-DD HH:mm
---
```

- `branch` と `commit` は調査時点の情報であり、他のブランチでの再利用を禁止するものではない。
- `updated` と `commit` は、記録内容の変更や再検証を行った場合に更新する。
- 関連する未コミットの変更を含めて調査した場合は、その旨を本文に記載する。
- ドキュメントには、根拠となるファイルパスや関数名など、後から確認できる情報を可能な限り残す。

### ブランチによる差異

ブランチが異なるだけでは、別ファイルを作成しない。

現在のブランチと既存の調査記録で実装や仕様が異なり、同一ファイルで管理すると誤解を招く場合に限り、ブランチ別のファイルを作成する。

ブランチ別ファイルは、元のファイル名に `.branch-<ブランチ名>` を付ける。

例：`payment-api.branch-feature-new-payment.md`

ブランチ名に `/` などのファイル名に適さない文字が含まれる場合は `-` に置換する。名前が衝突する場合は、一意になるように識別子を付与する。

ブランチ別ファイルを作成した場合は、元ファイルから参照できるようにする。

ブランチが統合され、記録が不要になった場合は、必要に応じて共通ファイルへ統合するか削除する。

### タスク完了時

調査を伴うタスクの完了時には、`.research-docs/` に残すべき再利用可能な知見が発生したかを確認する。

該当する知見があれば、作業完了報告の前に記録する。

既存の記録に変更がなく、新しい再利用可能な知見もない場合は、ファイルを更新する必要はない。

記録ファイルは必要に応じて自動で作成・更新してよい。
