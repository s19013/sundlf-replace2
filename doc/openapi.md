このプロジェクトでは

- dedoc/scramble
- openapi-typescript

を使い、laravelのリクエストレスポンスをtsの型として出力している

# コマンド

`mise generate:openapi`

# 流れ

laravelコンテナで,dedoc/scrambleを使いリクエストレスポンス情報をopenapiのjsonとして出力
↓
vueコンテナに移動
↓
schema.d.ts化

# 参照

- https://scramble.dedoc.co/
- https://zenn.dev/noxis/articles/b1c064a1516c03
- https://openapi-ts.dev/ja/introduction
