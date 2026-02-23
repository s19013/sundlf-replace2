import type { RouteRecordRaw } from 'vue-router'

const section = 'auth'
const prefix = `/${section}`

export const authRoutes: Array<RouteRecordRaw> = [
  {
    path: `${prefix}/register`,
    name: `${section}.register`,
    component: () => import('@/pages/auth/register/RegisterView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: `${prefix}/login`,
    name: `${section}.login`,
    component: () => import('@/pages/auth/login/LoginView.vue'),
    meta: { guestOnly: true },
  },
]
