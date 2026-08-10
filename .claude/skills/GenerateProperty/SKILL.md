---
name: generate-property
description: laravelのモデルにpropertyを自動生成
---

# @propertyの自動生成

モデルクラス本体に書く`@property`(属性の型情報)は手書きせず、`ide-helper:models`コマンドの出力を元に記入する。

## 手順

1. `mise exec:laravel "php artisan ide-helper:models --nowrite"`を実行し、`_ide_helper_models.php`に全モデルの候補を生成する
2. 出力された内容と実際のDBスキーマ・migrationファイルにズレがないか確認する
   - migrationファイルを実行後に編集した場合、`migrate:status`では実行済み扱いのままDBに反映されていないことがあるため注意する
   - ズレがあれば`php artisan migrate:fresh`などでDBを最新化してから、コマンドを再実行する
   - **注意**: `migrate:fresh`はローカル環境またはテスト用の使い捨てDBでのみ実行すること。保持が必要なDBや共有DBでは実行しないこと
3. 対応するモデルの既存の自動生成`@property`/`@property-read`ブロックを置換し、最新の出力だけをモデルクラス宣言の直前に貼り付ける
   - `@method`(クエリビルダーの`where〇〇`等)は貼り付けない。属性の型情報のみを記載する
4. `_ide_helper_models.php`はgitで追跡しないため、コピー後はそのまま放置してよい(次回`mise generate:ide-helper`実行時に上書きされる)
