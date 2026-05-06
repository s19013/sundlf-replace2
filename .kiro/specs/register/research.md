# Research & Design Decisions

---
**Purpose**: ディスカバリー調査の結果・アーキテクチャ検討・設計判断の根拠を記録する。

**Usage**:
- ディスカバリーフェーズの調査活動と結果をログに残す
- `design.md` に掲載するには詳細すぎる設計判断のトレードオフを記録する
- 将来の監査・再利用のための参考情報とリンクを提供する
---

## Summary

- **Feature**: `register`
- **Discovery Scope**: Extension（既存認証ドメインへの拡張）
- **Key Findings**:
  - 既存の `SPAAuthController` に `register` メソッドを追加し、同一コントローラで Login / Logout / User / Register を一元管理するパターンが最適
  - フロントエンドでは `loginByCredentials.ts` と同じアーキテクチャパターンで `registerByCredentials.ts` を実装し、エラーハンドリングをビュー層から分離する
  - `RegisterView.vue` はフォーム UI が実装済みで、API 呼び出し部分のみ未実装（TODO コメントあり）であり、変更コストが最小

---

## Research Log

### 既存認証パターンの分析

- **Context**: 登録機能を既存コードとどのように統合するか把握するため
- **Sources Consulted**: `SPAAuthController.php`, `LoginUsecase.php`, `authStore.ts`, `authApi.ts`, `loginByCredentials.ts`
- **Findings**:
  - コントローラは薄く Usecase への委譲のみ（ビジネスロジックを持たない）
  - Usecase は `__invoke` メソッドで実装し、FormRequest 経由でバリデーション
  - フロントの API コールは `ziggyRoute` で型安全に呼び出し
  - エラーハンドリングは `xxxByCredentials.ts` で一元化し、View はその結果のみを扱う
  - ログイン後は `router.push({ name: 'about' })` で AboutView にリダイレクト（LoginView より）
- **Implications**:
  - 同パターンで `RegisterUsecase` を実装する
  - フロント側は `registerByCredentials.ts` を新設してエラーハンドリングを一元化する

### RegisterView.vue の現状確認

- **Context**: フロントエンドの UI 実装状況の確認
- **Sources Consulted**: `frontend/src/pages/auth/register/RegisterView.vue`
- **Findings**:
  - フォーム UI はほぼ完成（ユーザー名・メール・パスワード・パスワード確認フィールドが実装済み）
  - パスワード一致チェックがクライアントサイドで実装済み（`password !== passwordConfirmation` 判定）
  - API 呼び出しとリダイレクトは TODO コメントのみで未実装
  - `authStore.isLoading` をボタンの `loading` / `disabled` プロパティに連携済み
- **Implications**:
  - `RegisterView.vue` の修正は最小限で済む（`registerByCredentials.ts` の呼び出しとリダイレクト処理の追加のみ）

### User モデルと型定義の確認

- **Context**: 登録時に渡すデータと返却データの型定義を確認
- **Sources Consulted**: `app/Models/User.php`, `MinimumUserResource.php`, `entities/auth/types/auth.ts`
- **Findings**:
  - User モデルは `name`, `email`, `password` を fillable として持つ
  - `password` は `hashed` cast が設定済みで `User::create()` 時に自動ハッシュ化される
  - レスポンスは既存の `MinimumUserResource`（`{id: int, name: string}`）を再利用可能
  - フロントの型定義は `schema.d.ts` から自動生成（LoginCredentials パターンと同様）
- **Implications**:
  - バックエンドでは `User::create()` でパスワードが自動ハッシュ化されるため手動ハッシュ化は不要
  - `mise generate:openapi` 実行後に `RegisterCredentials` 型を `components['schemas']['RegisterRequest']` から取得する

### ルーティングの確認

- **Context**: 登録 API のルート設計と既存ルートとの整合性確認
- **Sources Consulted**: `backend/routes/api.php`, `frontend/src/app/router/routes/auth.ts`, `AuthUserGuard.ts`
- **Findings**:
  - 既存 API ルートは `/api/spa/*` の名前空間で `spa.*` のルート名を使用
  - フロント側の `/auth/register` ページルートは定義済み（`meta: { guestOnly: true }`）
  - AboutView は `/about`（`name: 'about'`）で定義済み、AuthUserGuard により認証ガード済み
- **Implications**:
  - 新規 API ルートは `POST /api/spa/register` として `spa.register` の名前で追加
  - フロントのページルートは既存のものをそのまま使用

---

## Architecture Pattern Evaluation

| Option | Description | Strengths | Risks / Limitations | Notes |
|--------|-------------|-----------|---------------------|-------|
| 既存 SPAAuthController へのメソッド追加 | `register` メソッドを SPAAuthController に追加 | 一貫性が高い、ファイル変更最小 | コントローラが肥大化する可能性 | 現状の Login / Logout / User も同一コントローラで管理しており自然な拡張 |
| 別コントローラの新設 | RegisterController を新規作成 | 責任が明確 | 小規模な機能に対してオーバーエンジニアリング | 現時点では不要と判断 |

---

## Design Decisions

### Decision: 登録後のセッション開始方法

- **Context**: 登録完了後、ユーザーを自動ログイン状態にする方法の選択
- **Alternatives Considered**:
  1. `Auth::login($user)` でサーバー側セッション開始
  2. 登録後にクライアントが別途ログイン API を呼び出す
- **Selected Approach**: `Auth::login($user)` + `$request->session()->regenerate()` でサーバー側で直接セッションを開始
- **Rationale**: LoginUsecase と同一フローを踏襲し、クライアントの API 呼び出しを1回に削減、UX を向上させる
- **Trade-offs**: CSRF クッキー取得が必要だが既存フローと同様のため問題なし
- **Follow-up**: 実装後にセッション開始が正常に行われているか PHPUnit テストで確認

### Decision: RegisterRequest のバリデーション設計

- **Context**: バックエンドでどのバリデーションルールを適用するか
- **Alternatives Considered**:
  1. `password_confirmation` を Laravel の `confirmed` ルールで検証（`password.confirmed`）
  2. フロント側のみでパスワード確認を検証
- **Selected Approach**: バックエンドで `confirmed` ルールを使用（フロントと両方でバリデーション）
- **Rationale**: API 単体呼び出し時のセキュリティ確保、信頼境界でのバリデーション原則に準拠
- **Trade-offs**: フロント・バックエンド双方でバリデーションロジックが存在するが、これは意図的な多層防御
- **Follow-up**: Laravel の `confirmed` ルールはフィールド名 `password_confirmation` を自動認識

### Decision: フロント型定義の取得方法

- **Context**: `RegisterCredentials` 型をどこで定義するか
- **Alternatives Considered**:
  1. 手動で `entities/auth/types/auth.ts` に型を追加
  2. OpenAPI スキーマ生成後に `schema.d.ts` から取得
- **Selected Approach**: バックエンド実装後に `mise generate:openapi` を実行し、`LoginCredentials` と同様のパターンで `components['schemas']['RegisterRequest']` を参照
- **Rationale**: 型安全性を保ちつつ、スキーマとの同期を自動化する
- **Trade-offs**: 実装順序として バックエンド → スキーマ生成 → フロントエンド の順が必須
- **Follow-up**: `schema.d.ts` に `RegisterRequest` スキーマが出力されているか確認

---

## Risks & Mitigations

- メールアドレス重複バリデーション失敗時のフロントエラー表示 — 422 レスポンスのエラー内容を `registerByCredentials.ts` でフィールド別にマージして表示する
- 二重送信防止 — `authStore.isLoading` を利用して送信中はボタン無効化（RegisterView.vue にすでに連携済み）
- CSRF 保護 — Sanctum の `/sanctum/csrf-cookie` を登録前に呼び出す（既存 `getCsrfCookie()` 関数を再利用）

---

## References

- Laravel Sanctum SPA 認証: https://laravel.com/docs/sanctum#spa-authentication
- Feature-Sliced Design アーキテクチャ: https://feature-sliced.design/
- Laravel バリデーション（confirmed ルール）: https://laravel.com/docs/validation#rule-confirmed
