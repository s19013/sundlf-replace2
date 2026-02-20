import axios from 'axios'
import { useAuthStore } from '@/entities/auth/model/authStore'
import type { ApiErrorResponse, ValidationError } from '@/shared/types'
import type { LoginCredentials } from '@/entities/auth/types/auth'

const authStore = useAuthStore()

interface Result {
  isSuccess: boolean
  message: string | null
}

export async function loginByCredentials(credentials: LoginCredentials): Promise<Result> {
  try {
    await authStore.login(credentials)
    return { isSuccess: true, message: null }
  } catch (error: unknown) {
    if (axios.isAxiosError<ApiErrorResponse>(error)) {
      const status = error.response?.status

      if (status === 401) {
        return { isSuccess: false, message: 'メールアドレスまたはパスワードが正しくありません。' }
      }

      if (status === 422) {
        const data = error.response?.data as ValidationError
        return { isSuccess: false, message: Object.values(data.errors).flat().join('\n') }
      }
    }

    return { isSuccess: false, message: 'ログインに失敗しました。しばらくしてからお試しください。' }
  }
}
