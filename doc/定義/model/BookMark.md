# テーブル 属性

[book_mark.md](../db/book_marks.md)

# 追加属性

## isDeadlineApproaching

期限間近かどうか

残り1週間で消える場合true

## isInTrash

論理削除済みか

deleted_at is not nul = true

# 関数

## isOwner

### 引数

- string:userId

user_id と 引数userId が同じなら true

## countIncrease

count を +1

※ updated_at は更新させない -> 検索に影響がでるため

## hasBeenUpdatedSinceRetrieval

### 引数

- string:fetched_at

メモの`updated_at` == `fetched_at` の 場合 true

データベースにあるデータが、自分が取得した後に更新されてないか確認する。

## salvage

deleted_at を nullにする

# リレーション

ほかはテーブル定義を参照

## tags

中間テーブル(book_mark_tags) 経由でタグを取得

# 備考

## 状態

### 通常

通常の一覧･検索･閲覧･編集に表示される。
ゴミ箱関連の画面では表示されない。

### ゴミ箱

論理扱いされている状態
通常の一覧･検索･閲覧･編集には表示されない。
ゴミ箱関連の画面でのみ表示される。

### 完全削除済み

DBから削除され、復元できない。
