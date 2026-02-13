import { apiClient } from '@/shared/api'
import type { LoginForm, User } from '../types/auth'
import { route } from 'ziggy-js'
import { Ziggy } from '@/app/ziggy'

export function getCsrfCookie() {
  // SPAを認証するには、SPAの「ログイン」ページで最初に/sanctum/csrf-cookieエンドポイントにリクエストを送信して、アプリケーションのCSRF保護を初期化する必要ある。
  return apiClient.get(route('sanctum.csrf-cookie', undefined, undefined, Ziggy))
}

console.log(route('spa.login', undefined, undefined, Ziggy))

export async function login(credentials: LoginForm): Promise<User> {
  const response = await apiClient.post<{ user: User }>(
    route('spa.login', undefined, undefined, Ziggy),
    credentials,
  )
  return response.data.user
}

export function logout() {
  return apiClient.post(route('spa.logout', undefined, undefined, Ziggy))
}

export async function getCurrentUser(): Promise<User> {
  const response = await apiClient.get<{ user: User }>(
    route('auth.user', undefined, undefined, Ziggy),
  )
  return response.data.user
}
