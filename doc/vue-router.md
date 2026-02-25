# 新規追加したmetaタグの型定義

frontend/src/app/router/types.d.ts` にmetaタグの型を定義しています。

# ナビゲーションガード

- AuthUserGuard.ts laravelで言うauthガード
  - 未認証ユーザーはログインページ（例: `/login`）へリダイレクト
- GuestUserGuard.ts laravelで言うguestガード
  - 認証済みユーザーはホーム（例: `/`）へリダイレクト

index.ts でナビゲーションのたびにセッションの有効性を確認するガードも実行しています。
セッション切れが検出された場合は、ログインページへリダイレクトします。

# ルール

## 認証ユーザーだけ通す

metaタグに`requiresAuth:true`をつける

## 非認証ユーザーだけ通す

metaタグに`guestOnly:true`をつける
