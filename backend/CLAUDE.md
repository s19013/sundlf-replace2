# 構成

MVC + usecase

- usecase : ビジネスロジック、`__invoke`メソッドで実装

# 品質

- php-stan
- pint
- phpunit

## コマンド

```bash
# 全部テスト
mise exec:laravel "composer run test"

# phpstan
mise exec:laravel "composer run phpstan"

# pint
mise exec:laravel "composer run pint"

```

# 主要技術

- php
- laravel

# ライブラリ

- tightenco/ziggy
- dedoc/scramble
- laravel/sanctum

# 開発コマンド

```bash
# コンテナ内でのコマンド実行
mise exec:laravel "[command]"

```

# 日時類のフォーマットについて

- UTCでデータベースに保存
- UTCのISO8601形式でフロントに返す

# API

フロントエンド側で実装を進めやすいように以下の行動をしてください。

新規のAPIを定義したら、`mise generate:ziggy`を実行して、Ziggyのルート定義ファイルを再生成してください。
`mise generate:openapi`を実行して、リクエストレスポンス情報を`schema.d.ts`に出力してください。
