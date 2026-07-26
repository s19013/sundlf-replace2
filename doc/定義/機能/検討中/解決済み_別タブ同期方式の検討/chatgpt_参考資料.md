実装可能です。
やることは大きく分けて、**保存競合の検知**と、**競合内容をフォームへ表示する処理**の2つです。

## 推奨構成

これは一般に「楽観的ロック」と呼ばれる方式です。

編集画面を取得したときに、記事本文と一緒に以下のどちらかをVueへ渡します。

- `updated_at`
- 専用の `lock_version`

そして保存時に、その値もLaravelへ送ります。

```json
{
  "title": "編集後タイトル",
  "body": "編集後本文",
  "updated_at": "2026-07-26T17:30:00.000000Z"
}
```

Laravel側では、保存直前のDB上の値と比較します。

```php
$article = Article::findOrFail($id);

if ($article->updated_at->toISOString() !== $request->updated_at) {
    return response()->json([
        'message' => '他の画面で記事が更新されています。',
        'current' => [
            'title' => $request->title,
            'body' => $request->body,
        ],
        'saved' => [
            'title' => $article->title,
            'body' => $article->body,
            'updated_at' => $article->updated_at->toISOString(),
        ],
    ], 409);
}
```

ただし、単純な取得後比較よりも、**UPDATE文の条件に更新日時を含める方法**のほうが安全です。

```php
$updatedCount = Article::query()
    ->whereKey($id)
    ->where('updated_at', $request->updated_at)
    ->update([
        'title' => $request->title,
        'body' => $request->body,
        'updated_at' => now(),
    ]);

if ($updatedCount === 0) {
    $latestArticle = Article::findOrFail($id);

    return response()->json([
        'message' => '他の画面で記事が更新されています。',
        'saved' => [
            'title' => $latestArticle->title,
            'body' => $latestArticle->body,
            'updated_at' => $latestArticle->updated_at->toISOString(),
        ],
    ], 409);
}
```

こちらなら、

1. 現在値を取得
2. 比較
3. UPDATE

の間にさらに別の更新が入る問題を防げます。

## コンフリクト形式の表示

Vue側で409レスポンスを受け取ったら、現在フォームに入力されている内容と、サーバー上の最新版を結合します。

```ts
const formatConflict = (
  localValue: string,
  savedValue: string,
  savedAt: string,
) => {
  return [
    "<<<<<<< 現在の編集内容",
    localValue,
    "=======",
    savedValue,
    `>>>>>>> ${formatDate(savedAt)}（保存済み）`,
  ].join("\n");
};
```

例えばAxiosなら次のように処理できます。

```ts
try {
  await axios.put(`/api/articles/${articleId}`, {
    title: form.title,
    body: form.body,
    updated_at: form.updatedAt,
  });
} catch (error) {
  if (axios.isAxiosError(error) && error.response?.status === 409) {
    const saved = error.response.data.saved;

    form.body = formatConflict(form.body, saved.body, saved.updated_at);

    conflictDetected.value = true;
  }
}
```

結果としてtextareaにこのように表示できます。

```text
<<<<<<< 現在の編集内容
このタブで編集中だった内容
=======
別タブですでに保存された内容
>>>>>>> 2026/07/26 18:00:00（保存済み）
```

## 注意点

### 1. 「現在」という名前は少し分かりにくい

Gitの表記に寄せるなら、次のようにしたほうが利用者には分かりやすいです。

```text
<<<<<<< あなたの編集内容
この画面で入力していた内容
=======
サーバーに保存されている最新版
>>>>>>> 2026/07/26 18:00:00
```

「現在」がローカル側なのか、DB側の現在値なのか混乱しやすいためです。

### 2. `updated_at`の精度に注意

MySQLやPostgreSQLとLaravel、JSONの間で、マイクロ秒が欠落すると一致しない場合があります。

例えばDBでは、

```text
2026-07-26 18:00:00.123456
```

フロントでは、

```text
2026-07-26T18:00:00Z
```

となると、同じ更新でも不一致になります。

対策としては以下のどちらかです。

- 日時フォーマットと精度を統一する
- 整数の`lock_version`カラムを使う

長期的には`lock_version`方式のほうが分かりやすいです。

```php
Schema::table('articles', function (Blueprint $table) {
    $table->unsignedBigInteger('lock_version')->default(1);
});
```

保存時は次のようにします。

```php
$updatedCount = Article::query()
    ->whereKey($id)
    ->where('lock_version', $request->lock_version)
    ->update([
        'title' => $request->title,
        'body' => $request->body,
        'lock_version' => DB::raw('lock_version + 1'),
        'updated_at' => now(),
    ]);
```

### 3. 全フィールドをコンフリクト表示するか

記事に以下のような複数項目がある場合、

- タイトル
- 本文
- 公開状態
- カテゴリ
- 要約

すべてにGit形式を入れるのは扱いづらくなります。

特にチェックボックスやセレクトボックスにはコンフリクト記号を入れられません。

現実的には、

- 本文や概要などのtextareaはコンフリクト形式
- タイトルはローカル値と保存済み値を別々に表示
- 公開状態などは選択式でどちらを採用するか表示

という形が扱いやすいです。

### 4. コンフリクト表示後の再保存

競合表示をフォームに入れただけでは、次の保存でも古い`updated_at`のままなので再び409になります。

競合レスポンスを受けた時点で、フォームが保持する比較用日時を最新版へ更新します。

```ts
form.updatedAt = saved.updated_at;
```

その後、ユーザーがコンフリクト記号を手動で解消し、再保存します。

ただし、この解消中にさらに他の人が更新した場合は、もう一度競合になります。これは正常な動作です。

## さらに安全なレスポンス設計

Laravelからは、ローカル内容をそのまま返さなくても、Vue側ですでに保持しています。

そのためAPIは次の程度でも十分です。

```json
{
  "message": "他の画面で更新されています。",
  "code": "ARTICLE_UPDATE_CONFLICT",
  "latest": {
    "title": "保存済みタイトル",
    "body": "保存済み本文",
    "updated_at": "2026-07-26T09:00:00.000000Z"
  }
}
```

HTTPステータスは`409 Conflict`が適切です。

## 実装コストの目安

既存構成が単純なLaravel API＋Vueフォームなら、比較的軽いです。

### 最小構成

- 保存競合の検知
- 409レスポンス
- 本文textareaへのコンフリクト文字列挿入
- 解消後の再保存

目安は**半日〜1日程度**です。

### 実運用向け

- 複数フィールド対応
- 日時精度の調整
- テスト追加
- 競合時の警告UI
- 二重競合の確認
- ローカル値／保存済み値の選択機能
- 編集途中のデータ消失防止

目安は**1〜3日程度**です。

### 本格的な差分マージ

Gitのように、変更された行だけを解析し、自動的にマージ可能な部分はマージする場合は難易度が上がります。

例えば元データが、

```text
A
B
C
```

タブ1が、

```text
A
B変更
C
```

タブ2が、

```text
A
B
C変更
```

なら、本来は自動的に、

```text
A
B変更
C変更
```

と統合できます。

このためには編集開始時点のデータも必要です。

- 編集開始時の本文
- この画面で編集した本文
- DBに保存された最新版

この3つを使う「3-way merge」が必要になります。

単純なコンフリクト記号表示なら軽量ですが、本格的な自動マージまで行うなら**3〜7日以上**を見たほうがよいです。

## 結論

今回のイメージどおり、

- 更新ズレを検知
- `409 Conflict`を返す
- Vue側でGit風の文字列をtextareaに入れる
- ユーザーが手動で解消して再保存する

という実装は十分可能です。

コストも、本文中心の単純なフォームなら高くありません。
ただし、比較処理は「取得してからPHP上で比較」だけでなく、**UPDATEのWHERE条件に更新日時またはバージョンを含める**実装にしたほうが安全です。
