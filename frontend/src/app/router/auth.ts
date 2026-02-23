import type { RouteRecordRaw } from 'vue-router'

const as = 'auth'
const prefix = `/${as}`

export const authRoutes: Array<RouteRecordRaw> = [
  {
    path: `${prefix}/register`,
    name: `${as}.register`,
    component: () => import('@/pages/auth/register/RegisterView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: `${prefix}/login`,
    name: `${as}.login`,
    component: () => import('@/pages/auth/login/LoginView.vue'),
    meta: { guestOnly: true },
  },
]
