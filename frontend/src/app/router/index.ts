import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { AuthGuard } from './AuthUserGuard'
import { GuestGuard } from './GuestUserGuard'
import type { RouteRecordRaw } from 'vue-router'

const authGuard = new AuthGuard()
const guestGuard = new GuestGuard()

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'welcome',
    component: () => import('@/pages/welcome/WelcomeView.vue'),
  },
  ...authGuard.accessibleList,
  ...guestGuard.accessibleList,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // セッションはサーバーにある、トークンが失効してる可能性がある、別タブでログアウトしてる可能性がある
  // なので毎度サーバーに確認をとる必要がある。
  try {
    await authStore.checkAuth()
  } catch (error) {
    console.error('Auth check failed:', error)
    // ネットワークエラーなどで失敗した場合はログインページへリダイレクト
    if (to.meta.requiresAuth) {
      return { name: 'auth.login' }
    }
  }
})

router.beforeEach(authGuard.routeGuard)
router.beforeEach(guestGuard.routeGuard)

export default router
