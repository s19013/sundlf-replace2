# Project Structure

## Organization Philosophy

モノレポ構成。`backend/`（Laravel）と `frontend/`（Vue）を分離し、`mise` タスクで統合。

- **Backend**: MVC + Usecase パターン（ドメイン別ディレクトリ）
- **Frontend**: Feature-Sliced Design（FSD）アーキテクチャ（レイヤー別 → ドメイン別）

---

## Backend Structure (`backend/app/`)

### Controllers
**Location**: `app/Http/Controllers/[Domain]/`
**Purpose**: リクエスト受け取り → Usecase への委譲のみ。ビジネスロジックを持たない。
**Example**: `Controllers/Auth/SPAAuthController.php`

```php
// コントローラは薄く保つ: リクエストを受け取り、Usecaseを呼び出す
public function login(LoginRequest $request, LoginUsecase $usecase): JsonResponse {
    return $usecase($request);  // __invoke で処理を委譲
}
```

### Usecases
**Location**: `app/Usecases/[Domain]/[SubDomain]/`
**Purpose**: ビジネスロジックの実装。`__invoke` メソッドで完結。
**Example**: `Usecases/Auth/SPA/LoginUsecase.php`

### Models
**Location**: `app/Models/`
**Purpose**: Eloquent モデル定義

### Requests / Resources
**Location**: `app/Http/Requests/[Domain]/`, `app/Http/Resources/`
**Purpose**: バリデーションとレスポンス整形

---

## Frontend Structure (`frontend/src/`)

FSD（Feature-Sliced Design）レイヤー構造:

### App Layer
**Location**: `src/app/`
**Purpose**: アプリ起動設定（main.ts、router、グローバルスタイル）
**Example**: `app/router/routes/auth.ts` でルーティング定義

### Entities Layer
**Location**: `src/entities/[domain]/`
**Purpose**: ドメインの型・状態・API呼び出し
**Subdirs**: `types/`（型定義）、`model/`（Pinia ストア）、`api/`（API 関数）
**Example**: `entities/auth/model/authStore.ts`

### Pages Layer
**Location**: `src/pages/[domain]/`
**Purpose**: ページコンポーネント（ルートに対応するビュー）

### Shared Layer
**Location**: `src/shared/`
**Purpose**: 横断的ユーティリティ
**Subdirs**: `api/`（apiClient, ziggyRoute）、`types/`（自動生成スキーマ）、`ui/`、`layout/`

---

## Naming Conventions

- **PHP ファイル**: PascalCase（`LoginUsecase.php`）
- **Vue ファイル**: PascalCase（`LoginForm.vue`）
- **TypeScript ファイル**: camelCase（`authStore.ts`, `authApi.ts`）
- **Pinia ストア**: `use[Domain]Store` パターン

## Import Organization

```typescript
// フロントエンド: '@/' エイリアスは src/ を指す
import { apiClient, ziggyRoute } from '@/shared/api'
import type { User } from '../types/auth'

// PrimeVue: 単一 import にまとめる
import { InputText, Button, Message } from 'primevue'

// ES modules の名前付きエクスポート
export function login(credentials: LoginCredentials): Promise<User> { ... }
```

## API Integration Pattern

新 API 追加時の手順:
1. `backend/routes/api.php` にルート定義
2. `app/Http/Controllers/` にコントローラ追加
3. `app/Usecases/` にビジネスロジック実装
4. `mise generate:ziggy` → `mise generate:openapi` 実行
5. `frontend/src/entities/[domain]/api/` に API 関数追加

---
_Document patterns, not file trees. New files following patterns shouldn't require updates_
