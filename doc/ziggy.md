本プロジェクトではziggyを導入している

### 目的

apiのルートが変わっても変更が少なくて済むようにするため

### 実行

バックエンドに新たなルートを作成
↓
`mise generate:ziggy`
を実行し、以下のファイルを生成、更新する

- `frontend/src/shared/api/ziggy.js`
- `frontend/src/shared/types/ziggy.d.ts`

### 補足

`frontend/env.d.ts`
にて
`@/shared/api/ziggy`型を追加している

### 参考

- [Ziggyガイド - LaravelとJavaScriptを繋ぐ魔法のツール](https://zenn.dev/oz006/articles/99aba4b4626f89)
- [Ziggy](https://github.com/tighten/ziggy?tab=readme-ov-file)
