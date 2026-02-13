import { apiClient, webClient } from '@/shared/api'
import type { LoginCredentials, User } from '../types/auth'

export function getCsrfCookie() {
  // SPAを認証するには、SPAの「ログイン」ページで最初に/sanctum/csrf-cookieエンドポイントにリクエストを送信して、アプリケーションのCSRF保護を初期化する必要ある。
  // しかし、/sanctum/csrf-cookie は先頭にapiをつけないのでwebClientを使う必要がある。
  return webClient.get(`/sanctum/csrf-cookie`)
}

export async function login(credentials: LoginCredentials): Promise<User> {
  const response = await apiClient.post<{ user: User }>('/login', credentials)
  return response.data.user
}

export function logout() {
  return apiClient.post('/logout')
}

export async function getCurrentUser(): Promise<User> {
  const response = await apiClient.get<{ user: User }>('/user')
  return response.data.user
}
