import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { User, LoginForm } from '../types/auth'
import {
  getCsrfCookie,
  login as apiLogin,
  logout as apiLogout,
  getCurrentUser,
} from '../api/authApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!user.value)

  async function login(credentials: LoginForm) {
    isLoading.value = true
    user.value = null
    try {
      await getCsrfCookie()
      user.value = await apiLogin(credentials)
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
      // ページ移動やリロード時にuserが破棄された場合は再度取得
      user.value = await getCurrentUser()
    } catch {
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  return { user, isAuthenticated, isLoading, login, logout, checkAuth }
})
