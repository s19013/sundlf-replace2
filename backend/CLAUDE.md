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

```

# 主要技術

- php
- laravel

# 開発コマンド

```bash
# コンテナ内でのコマンド実行
mise exec:laravel "[command]"

```
