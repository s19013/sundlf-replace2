// 認証済みのみ許可
import { Guard } from './Guard'
import type { RouteRecordRaw, RouteRecordName, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/entities/auth/model/authStore'

export class AuthGuard extends Guard {
  protected setRedirectName(): RouteRecordName {
    return 'auth.login'
  }

  // ログイン者だけアクセスできる画面
  protected setAccessibleList(): RouteRecordRaw[] {
    return [
      {
        path: '/about',
        name: 'about',
        component: () => import('@/pages/about/ui/AboutView.vue'),
        meta: { requiresAuth: true },
      },
    ]
  }

  // ログインしてないのにログイン者のみアクセスできるページにアクセスしようとしてるのを防ぐ
  public shouldRedirect(to: RouteLocationNormalized): boolean {
    const { isAuthenticated } = useAuthStore()

    // ログイン必須ページに未ログインでアクセスしようとしているならリダイレクト
    if (to.meta.requiresAuth && !isAuthenticated) {
      return true
    }

    return false
  }
}
