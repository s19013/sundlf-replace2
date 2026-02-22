import axios from 'axios'
import { useAuthStore } from '@/entities/auth/model/authStore'
import type { ApiErrorResponse, ValidationError } from '@/shared/types'
import type { LoginCredentials } from '@/entities/auth/types/auth'

interface Result {
  isSuccess: boolean
  message: string | null
}

export async function loginByCredentials(credentials: LoginCredentials): Promise<Result> {
  const authStore = useAuthStore()
  try {
    await authStore.login(credentials)
    return { isSuccess: true, message: null }
  } catch (error: unknown) {
    if (axios.isAxiosError<ApiErrorResponse>(error)) {
      const { response } = error

      if (!response) {
        return {
          isSuccess: false,
          message: 'ログインに失敗しました。しばらくしてからお試しください。',
        }
      }

      const { status, data } = response

      if (status === 401) {
        return {
          isSuccess: false,
          message: 'メールアドレスまたはパスワードが正しくありません。',
        }
      }

      if (status === 422) {
        const validationErrors = (data as ValidationError).errors
        if (validationErrors) {
          return {
            isSuccess: false,
            message: Object.values(validationErrors).flat().join('\n'),
          }
        }
      }
    }

    return {
      isSuccess: false,
      message: 'ログインに失敗しました。しばらくしてからお試しください。',
    }
  }
}
