# 構成

Feature-Sliced Design(fsd)アーキテクチャを採用し、機能ごとにモジュール化された構造を実現

# code style

ES modulesを使う
インポートは分割形式で (例: import {foo} from 'hoge')

# 品質

- eslint
- prettier
- vitest

## コマンド

```bash
# eslint修正
mise exec:vue "pnpm run lint:fix"

# フロントのコードフォーマット
mise exec:vue "pnpm run format"

# typescriptの型チェック
mise exec:vue "pnpm run type-check"

# フロントのテスト
mise exec:vue "pnpm run test:unit"

```

# 主要技術

- vue3
- typescript
- pinia
- vite
- vitest
- eslint
- prettier
- axios
- primevue

# 開発コマンド

```bash
# コンテナ内でのコマンド実行
mise exec:vue "[command]"

```

# 日時類のフォーマットについて

- utcで受け取る
- day.jsを使いローカルの日時に修正
- バックへの送信時はutcに変換して送信する

# api

基本的には @frontend/src/shared/api/index.ts の ziggyRoute を使ってapiを呼び出してください。
それが難しい場合は、ziggyのrouteを使ってください。

# その他

- 送信ボタン類などの通信を伴うボタンは連打できないようにする
