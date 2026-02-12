import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { User, LoginCredentials } from '../types/auth'
import {
  getCsrfCookie,
  login as apiLogin,
  logout as apiLogout,
  getCurrentUser,
} from '../api/authApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  async function login(credentials: LoginCredentials) {
    isLoading.value = true
    try {
      await getCsrfCookie()
      const response = await apiLogin(credentials)
      user.value = response.data.user
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      await apiLogout()
    } finally {
      user.value = null
    }
  }

  async function checkAuth() {
    if (user.value) return

    isLoading.value = true
    try {
      const response = await getCurrentUser()
      user.value = response.data
    } catch {
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  return { user, isAuthenticated, isLoading, login, logout, checkAuth }
})
