# 新規追加したmetaタグの型定義

`frontend/src/app/router/types.d.ts`

にmetaタグの型を定義してる

# ナビゲーションガード

- AuthUserGuard.ts laravelで言うauthガード
- GuestUserGuard.ts laravelで言うguestガード

index.tsで毎度セッションが切れていないかのガードも実行している

# ルール

## 認証ユーザーだけ通す

metaタグに`requiresAuth:true`をつける

## 非認証ユーザーだけ通す

metaタグに`guestOnly:true`をつける
