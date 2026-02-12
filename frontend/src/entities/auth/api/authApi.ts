import axios from 'axios'
import { apiClient } from '@/shared/api/apiClient'
import type { LoginCredentials, User } from '../types/auth'

const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

export function getCsrfCookie() {
  return axios.get(`${backendBaseUrl}/sanctum/csrf-cookie`, {
    withCredentials: true,
  })
}

export function login(credentials: LoginCredentials) {
  return apiClient.post<{ user: User }>('/login', credentials)
}

export function logout() {
  return apiClient.post('/logout')
}

export function getCurrentUser() {
  return apiClient.get<User>('/user')
}
