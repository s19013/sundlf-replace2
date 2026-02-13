import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/pages/home/ui/HomeView.vue'
import { useAuthStore } from '@/entities/auth/model/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/login/ui/LoginView.vue'),
    },
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true },
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
  await authStore.checkAuth()

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
