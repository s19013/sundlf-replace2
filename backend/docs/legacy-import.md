# legacy:import コマンド

旧システム「my-wiki」のSQLダンプ(`database/my-wiki.sql`)から、新システムのDBへデータを移行するためのArtisanコマンド。1回限りのデータ移行を想定した使い捨てコマンドであり、通常運用では実行しない。

## 対象テーブル

| 旧システム | 新システム | 備考 |
| --- | --- | --- |
| `users` | `users` | password(bcryptハッシュ)はそのままコピー、再ハッシュしない |
| `articles` | `articles` | `star`カラムは旧データに存在しないため`0`固定で投入。`has_tags`は旧`article_tags`にtag_id非NULLの行が存在するかで判定(後述) |
| `tags` | `tags` | `user_id`+`name`の重複がある場合、条件に応じてスキップまたは名前をリネームして投入(後述) |
| `book_marks` | `book_marks` | `star`カラムは旧データに存在しないため`0`固定で投入。`has_tags`は旧`book_mark_tags`にtag_id非NULLの行が存在するかで判定(後述) |
| `article_tags` | `article_tags` | `tag_id`がNULLの行は移行しない(`articles.has_tags`へ意味を統合。後述) |
| `book_mark_tags` | `book_mark_tags` | `tag_id`がNULLの行は移行しない(`book_marks.has_tags`へ意味を統合。後述) |

旧システムの`password_resets`, `personal_access_tokens`, `failed_jobs`は新システムで再生成される情報のため移行対象外。

## 事前準備: legacy DBへのダンプ投入

同一MariaDBサーバー(`sundlf-mariadb`コンテナ)内に、`sundlf_legacy`という一時データベースを作成し、そこにダンプを投入する。

```bash
# legacy用DB作成(root権限で1回のみ)
docker compose exec mariadb mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS sundlf_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ダンプ投入(-T必須: docker compose execのデフォルトpseudo-tty割り当てでstdinリダイレクトが壊れるため)
docker compose exec -T mariadb mysql -uroot -proot sundlf_legacy < backend/database/my-wiki.sql
```

`.env`の`LEGACY_DB_*`が上記の接続先(`sundlf_legacy`, root)を指すよう設定済み。別のDB名・認証情報を使う場合は`.env`を書き換える。

## 使い方

```bash
# 件数と重複検出結果のみ表示(書き込みなし)
mise exec:laravel "php artisan legacy:import --dry-run"

# 実行(usersテーブルが空の状態で実行すること。後述)
mise exec:laravel "php artisan legacy:import"

# 既存データを全削除してからやり直す場合
mise exec:laravel "php artisan legacy:import --fresh"
```

### オプション

| オプション | 説明 |
| --- | --- |
| `--dry-run` | 書き込みを行わず、legacy側の各テーブル件数と、tagsの重複検出結果(スキップ対象・リネーム対象)のみ表示する |
| `--fresh` | 投入前に対象6テーブルを子→親の順(外部キー制約を一時無効化して)で全削除する。確認プロンプトあり |
| `--chunk=500` | legacy側読み込み・書き込みのchunkサイズ(デフォルト500) |

移行全体は`DB::transaction()`で包まれており、途中でエラーが発生した場合は自動的にロールバックされ、DBは実行前の状態のまま残る。

## 実行前提・注意事項

- **`php artisan migrate:fresh`(seedなし)直後の、usersテーブルが空の状態で実行すること。** `migrate:fresh --seed`のように`DatabaseSeeder`でテストユーザーを先に作ると、自動採番のid=1が旧データのid=1(実際のプロジェクトオーナー)と主キー衝突し、失敗する。
  - `--fresh`なしで`users`に既存行がある場合はコマンドが警告と確認プロンプトを出す。
- ローカル開発用のテストユーザーが必要な場合は、`legacy:import`実行**後**に`php artisan db:seed`を実行する。既存データ投入後は自動採番が最大idの次から始まるため衝突しない。
- 旧データのuser idには欠番(3, 13等)があるが、articles/tags/book_marksからの参照は全て解決可能(孤立FK参照はない)ことを確認済み。欠番のまま投入してよい。

## has_tagsの算出とtag_id NULL行の扱い

旧システムでは、メモ・ブックマークにタグが1つも紐付いていない状態を、`article_tags`/`book_mark_tags`に`tag_id = NULL`の行を1件作ることで表現していた。新システムではこの意味を`articles.has_tags`/`book_marks.has_tags`(boolean)に一本化し、中間テーブルの`tag_id`はNOT NULL制約に変更したため、以下のように移行する。

- `article_tags`/`book_mark_tags`の`tag_id`がNULLの行は、新システムの中間テーブルへ一切コピーしない(スキップする)。
- `articles`/`book_marks`を投入する前に、旧`article_tags`/`book_mark_tags`から`tag_id`が非NULLの行を持つ`article_id`/`book_mark_id`の集合を集計し、該当すれば`has_tags = true`、しなければ`false`として投入する。

`--dry-run`実行時に、スキップされる`tag_id`NULL行数と、`has_tags = true`になる件数(articles/book_marksそれぞれ)が表示される。

## tagsの重複データについて

新スキーマの`tags`テーブルには`unique(['user_id', 'name'])`制約があるが、旧データには同一`user_id`+`name`の組が3組存在する。

| id (旧) | name | user_id | 備考 |
| --- | --- | --- | --- |
| 306 / 548 | 統計学 | 1 | id306は有効、id548は論理削除済み |
| 420 / 421 | 10 | 1 | 両方とも論理削除済み |
| 709 / 715 | まとめ 要約 | 24 | id709は論理削除済み、id715は有効 |

2件目以降(走査順で後に検出された方=548, 420, 421, 709)について、以下のルールで判定する。

- **論理削除済み(`deleted_at`が非NULL)かつ`article_tags`/`book_mark_tags`のどちらからも参照されていない場合**: 投入自体をスキップする。実データ確認済みで、上記4件(548, 420, 421, 709)はいずれもこの条件に該当し、どこからも参照されていない孤立レコードだった。スキップした分だけtags件数は減る(751件 → 747件)。
- **上記に該当しない場合(有効なタグ同士の重複、または実際に参照が残っている場合)**: `article_tags`/`book_mark_tags`から`tag_id`として直接参照されている可能性があるため安全側に倒し、idはそのまま、名前だけ`"{元の名前}(旧タグID:{id})"`にリネームして両方投入する。

`--dry-run`で実行すると、スキップ対象・リネーム対象それぞれを事前に確認できる。

## 移行後の後片付け(任意)

移行が完了しデータ検証も済んだら、以下は削除して問題ない。

- `sundlf_legacy`データベース(`docker compose exec mariadb mysql -uroot -proot -e "DROP DATABASE sundlf_legacy;"`)
- `.env` / `.env.example`の`LEGACY_DB_*`設定
- `config/database.php`の`legacy`接続定義
- `app/Console/Commands/ImportLegacyDataCommand.php`本体

## 検証手順

1. `mise exec:laravel "composer run pint"` / `mise exec:laravel "composer run phpstan"` でコード品質を確認。
2. `--dry-run`でlegacy側の件数(users 24, articles 2,324, tags 751, book_marks 5,899, article_tags 4,017, book_mark_tags 17,532)、tag重複3組(4件)の判定結果(スキップ/リネーム)、`tag_id`NULL行のスキップ件数、`has_tags = true`になる件数を確認。
3. マイグレーション変更(`has_tags`追加、`tag_id` NOT NULL化)を反映するため`migrate:fresh`を実行してから`legacy:import`を実行し、`SUCCESS`で終了することを確認。
4. `php artisan tinker`等で件数を確認する。`tags`は現状のデータではスキップ4件により`747`件になる見込み(スキップ・リネームの内訳は`--dry-run`の出力と一致するはず)。他のテーブルはlegacy側の件数とそのまま一致することを確認。
5. `DB::table('articles')->where('has_tags', true)->count()` / `DB::table('book_marks')->where('has_tags', true)->count()`が`--dry-run`で表示された件数と一致すること、`article_tags`/`book_mark_tags`に`tag_id IS NULL`の行が存在しないことを確認。
