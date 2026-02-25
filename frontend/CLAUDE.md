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

- typescript
- vue3

# ライブラリ

- @jamescoyle/vue-icon
- @mdi/js
- @primeuix/themes
- axios
- dayjs
- pinia
- primevue
- tailwindcss-primeui
- vue-router
- ziggy-js

# 開発コマンド

```bash
# コンテナ内でのコマンド実行
mise exec:vue "[command]"

```

# 日時類のフォーマットについて

- UTCのISO8601形式で受け取る
- day.jsを使いローカルの日時に修正
- バックへの送信時はutcに変換して送信する

# api

基本的には `frontend/src/shared/api/index.ts` の ziggyRoute を使ってapiを呼び出してください。
それが難しい場合は、ziggyのrouteを使ってください。

# レスポンシブ

ブレークポイントは以下の通りです。

```css
/* スマホ */
@media (max-width: 480px) {
}

/* タブレット */
@media (min-width: 481px) and (max-width: 768px) {
}

/* PC */
@media (min-width: 769px) {
}
```

基本的にはスマホとpcだけで良いですが、タブレットでスマホレイアウトだと見づらい場合にはタブレット用のレイアウトを追加してください。

# primeVueのコンポーネントの呼び出し

基本的には`from 'primevue'`からまとめて呼び出す形にして、1行にまとめてください。

### ok

```ts
import { InputText, Password, Button, Message } from 'primevue'
```

### ng

```ts
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
```

### 例外

長くなる場合には分けて良い。

# mdi

primevueでmdiを使う場合にはSvgIconコンポーネントを使う必要がある。
SvgIconコンポーネントは`main.ts`で読み込んでいるためどこでも呼び出せる

`<SvgIcon type="mdi" path="アイコンのパス" />`

# その他

- 送信ボタン類などの通信を伴うボタンは連打できないようにする
