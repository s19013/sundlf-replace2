import axios from 'axios'
import { apiClient } from '@/shared/api/apiClient'
import type { LoginCredentials, User } from '../types/auth'

export function getCsrfCookie() {
  const backendWebUrl = import.meta.env.BACKEND_WEB_URL

  if (backendWebUrl === null) {
    throw new Error('backendWebUrl が設定されていません')
  }

  return axios.get(`${backendWebUrl}/sanctum/csrf-cookie`, {
    withCredentials: true,
  })
}

export async function login(credentials: LoginCredentials): Promise<User> {
  const response = await apiClient.post<{ user: User }>('/login', credentials)
  return response.data.user
}

export function logout() {
  return apiClient.post('/logout')
}

export async function getCurrentUser(): Promise<User> {
  const response = await apiClient.get<User>('/user')
  return response.data
}
