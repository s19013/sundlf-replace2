import { apiClient, ziggyRoute } from '@/shared/api'
import type { LoginCredentials, RegisterCredentials, User } from '../types/auth'

// CSRF cookieがブラウザに自動セットされるだけ帰り値はPromise<void>で良い
export function getCsrfCookie(): Promise<void> {
  // SPAを認証するには、SPAの「ログイン」ページで最初に/sanctum/csrf-cookieエンドポイントにリクエストを送信して、アプリケーションのCSRF保護を初期化する必要ある。
  return apiClient.get(ziggyRoute('sanctum.csrf-cookie'))
}

export async function register(credentials: RegisterCredentials): Promise<User> {
  const response = await apiClient.post<{ user: User }>(ziggyRoute('spa.register'), credentials)
  return response.data.user
}

export async function login(credentials: LoginCredentials): Promise<User> {
  const response = await apiClient.post<{ user: User }>(ziggyRoute('spa.login'), credentials)
  return response.data.user
}

export function logout(): Promise<void> {
  return apiClient.post(ziggyRoute('spa.logout'))
}

export async function getCurrentUser(): Promise<User> {
  const response = await apiClient.get<{ user: User }>(ziggyRoute('spa.user'))
  return response.data.user
}
