本プロジェクトではziggyを導入している

# 目的

apiのルートが変わっても変更が少なくて済むようにするため

# 実行

バックエンドに新たなルートを作成
↓
`mise generate:ziggy`
を実行し、以下のファイルを生成、更新する

- `frontend/src/shared/api/ziggy.js`
- `frontend/src/shared/types/ziggy.d.ts`

# 補足

## 型

`frontend/env.d.ts`
にて
`@/shared/api/ziggy`型を追加している

## ziggyRoute.ts

`frontend/src/shared/api/ziggyRoute.ts`
に独自のパーツを用意した

### 目的

https://github.com/tighten/ziggy?tab=readme-ov-file#importing-the-route-function

@routes BladeディレクティブがないとZiggyの設定はグローバルには利用できないため、route()関数に手動で渡す必要があり以下のようになる

`route('sanctum.csrf-cookie', undefined, undefined, Ziggy)`

長ったらしく面倒なため短くかけるようにした。

# 参考

- [Ziggyガイド - LaravelとJavaScriptを繋ぐ魔法のツール](https://zenn.dev/oz006/articles/99aba4b4626f89)
- [Ziggy](https://github.com/tighten/ziggy?tab=readme-ov-file)
