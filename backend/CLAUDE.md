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

# 開発コマンド

```bash
# コンテナ内でのコマンド実行
mise exec:laravel "[command]"

```

# 日時類のフォーマットについて

- UTCでデータベースに保存
- UTCのISO8601形式でフロントに返す

# api

新規のapiを定義したら。`mise generate:ziggy`を実行して、ファイルを再生成してください。
