# Technology Stack

## Architecture

バックエンド・フロントエンド分離型 SPA。Docker コンテナで各サービスを独立させ、mise タスクランナーで統合管理する。

## Core Technologies

### Backend
- **Language**: PHP 8.2+
- **Framework**: Laravel 12
- **Architecture**: MVC + Usecase パターン（ビジネスロジックは `__invoke` メソッドを持つ Usecase クラスに分離）
- **Auth**: Laravel Sanctum（SPA Cookie 認証）
- **API Docs**: Scramble（OpenAPI 仕様自動生成）
- **Route Sharing**: Ziggy（Laravel ルートを JS へエクスポート）

### Frontend
- **Language**: TypeScript（strict mode）
- **Framework**: Vue 3（Composition API）
- **Build**: Vite
- **State**: Pinia
- **UI**: PrimeVue + TailwindCSS（tailwindcss-primeui）
- **HTTP**: Axios
- **Date**: Day.js（UTC ↔ ローカル変換）
- **Router**: vue-router 5

### Database
- MariaDB（Docker コンテナ）

## Development Standards

### Type Safety
- Backend: PHP-Stan（larastan）で静的解析
- Frontend: TypeScript strict、vue-tsc で型チェック
- API: OpenAPI → `schema.d.ts` 自動生成による型安全な通信

### Code Quality
- Backend: Laravel Pint（コードフォーマット）、PHPStan（静的解析）
- Frontend: ESLint + oxlint + Prettier

### Testing
- Backend: PHPUnit
- Frontend: Vitest（unit）、Playwright（e2e）

## Development Environment

### Required Tools
- Docker + Docker Compose
- mise（タスクランナー・ツール管理）
- Node.js 20+
- pnpm（フロントエンドパッケージマネージャ）

### Common Commands
```bash
# バックエンド内でコマンド実行
mise exec:laravel "[command]"

# フロントエンド内でコマンド実行
mise exec:vue "[command]"

# 全テスト（backend）
mise exec:laravel "composer run test"

# フロントテスト
mise exec:vue "pnpm run test:unit"

# Ziggy ルート再生成
mise generate:ziggy

# OpenAPI スキーマ再生成
mise generate:openapi
```

## Key Technical Decisions

- **Sanctum CSRF認証**: SPA ログイン前に `/sanctum/csrf-cookie` を呼び出して CSRF 保護を初期化
- **datetime**: DB は UTC 保存、API は UTC ISO8601 形式、フロントは dayjs でローカル変換
- **コード生成フロー**: 新 API 追加後は `mise generate:ziggy` → `mise generate:openapi` の順で実行必須

---
_Document standards and patterns, not every dependency_
