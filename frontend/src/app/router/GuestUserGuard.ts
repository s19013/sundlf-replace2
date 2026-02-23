// 未認証のみ許可
import { Guard } from './Guard'
import type { RouteRecordRaw, RouteRecordName, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { authRoutes } from './auth'

export class GuestGuard extends Guard {
  protected setRedirectName(): RouteRecordName {
    /** todo:後で変更予定 */
    return 'auth.login'
  }

  // 非ログイン者だけアクセスできる画面
  protected setAccessibleList(): RouteRecordRaw[] {
    return [...authRoutes]
  }

  // ログイン者が非ログイン者のみアクセスできるページにアクセスしようとしてるのを防ぐ
  public shouldRedirect(to: RouteLocationNormalized): boolean {
    const { isAuthenticated } = useAuthStore()

    // 非ログインユーザーかつ、非ログインユーザー専用ページなら通す
    if (to.meta.guestOnly && !isAuthenticated) {
      return false
    }

    return true
  }
}
