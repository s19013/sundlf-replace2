# ide-helper

このプロジェクトでは

- barryvdh/laravel-ide-helper

をつかってモデル類の`@property`などを定義したファイルを出力し、補完などが効くようにしている。

# 生成ファイル

- `_ide_helper.php`
- `_ide_helper_models.php`

# コマンド

```
mise generate:ide-helper

```

# 補足

出力されるファイルはgitで追跡しないため、最初は`mise generate:init`でコードから出力されるファイル類を出力すること
