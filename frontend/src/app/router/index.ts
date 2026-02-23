import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/entities/auth/model/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'welcome',
      component: () => import('@/pages/welcome/WelcomeView.vue'),
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/pages/auth/register/RegisterView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/auth/login/LoginView.vue'),
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('@/pages/about/ui/AboutView.vue'),
      meta: { requiresAuth: true },
    },
  ],
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
      return { name: 'login' }
    }
  }

  // 非ログインユーザー制御
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      return { name: 'login' }
    }
  }

  // ログインユーザー制御
  if (to.name === 'login') {
    if (authStore.isAuthenticated) {
      return { name: 'home' }
    }
  }
})

export default router
