import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { AuthUserGuard } from './Guard/AuthUserGuard'
import { GuestUserGuard } from './Guard/GuestUserGuard'
import type { RouteRecordRaw } from 'vue-router'

const authUserGuard = new AuthUserGuard()
const guestUserGuard = new GuestUserGuard()

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'welcome',
    component: () => import('@/pages/welcome/WelcomeView.vue'),
  },
  ...authUserGuard.accessibleList,
  ...guestUserGuard.accessibleList,
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: { name: 'welcome' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
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

router.beforeEach(authUserGuard.routeGuard)
router.beforeEach(guestUserGuard.routeGuard)

export default router
