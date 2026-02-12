import axios from 'axios'
import { apiClient } from '@/shared/api/apiClient'
import type { LoginCredentials, User } from '../types/auth'

const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

export function getCsrfCookie() {
  return axios.get(`${backendBaseUrl}/sanctum/csrf-cookie`, {
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
