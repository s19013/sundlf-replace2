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

## 言語設定

- 常に日本語で会話する
- コメントも日本語で記述する
- エラーメッセージの説明も日本語で行う
- ドキュメントも日本語で生成する

## 調査結果の記録と再利用

同じ内容を何度も調査することを防ぐため、調査によって判明した再利用可能な情報は `.research-docs/` 配下にMarkdown形式で記録する。
コードや仕様の調査を始める前に、まず `.research-docs/` 配下に関連する調査結果がないか確認すること。

既存の調査結果がある場合は、その内容を前提として、不足している部分や変更されている可能性がある部分を追加で調査すること。最初から同じ調査をやり直さないこと。
調査によって新しい事実が判明した場合や、既存の記録が古くなっていた場合は、該当する調査ドキュメントを追記・更新、もしくは破棄、新規作成すること。

記録する内容の例：

- データや処理の流れ
- クラス、関数、テーブル、APIなどの役割
- どの機能から参照・使用されているか
- 現在使用されていないと判断した処理やテーブル
- 調査時に判明した仕様や制約
- 判断の根拠となったファイルやシンボル
- 未確認事項や推測

一時的な作業状況、今回の変更内容だけに関係する情報、コードを見ればすぐ分かる単純な情報は、原則として記録しない。
事実、推測、未確認事項を区別して記載すること。
