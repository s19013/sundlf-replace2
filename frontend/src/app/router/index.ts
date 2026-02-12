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

  // 非ログインユーザー制御
  if (to.meta.requiresAuth) {
    await authStore.checkAuth()
    if (!authStore.isAuthenticated) {
      return { name: 'login' }
    }
  }

  // ログインユーザー制御
  if (to.name === 'login') {
    await authStore.checkAuth()
    if (authStore.isAuthenticated) {
      return { name: 'home' }
    }
  }
})

export default router
