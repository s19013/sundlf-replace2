import { describe, it, expect, vi, beforeEach } from 'vitest'
import axios from 'axios'
import type { RegisterCredentials } from '../types/auth'

// authStoreをモック
const mockRegister = vi.fn()
vi.mock('@/entities/auth/model/authStore', () => ({
  useAuthStore: () => ({
    register: mockRegister,
  }),
}))

import { registerByCredentials } from '../model/registerByCredentials'

const validCredentials: RegisterCredentials = {
  name: 'テストユーザー',
  email: 'test@example.com',
  password: 'password123',
  password_confirmation: 'password123',
}

describe('registerByCredentials', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('登録成功時にisSuccess: trueとmessage: nullを返すこと', async () => {
    mockRegister.mockResolvedValue(undefined)

    const result = await registerByCredentials(validCredentials)

    expect(result.isSuccess).toBe(true)
    expect(result.message).toBeNull()
  })

  it('422エラー時に全フィールドのエラーメッセージを改行結合して返すこと', async () => {
    const axiosError = new axios.AxiosError('Validation Error')

    axiosError.response = {
      status: 422,
      data: {
        message: 'バリデーションエラー',
        errors: {
          name: ['ユーザー名は必須です'],
          email: ['このメールアドレスは既に使用されています'],
        },
      },
      headers: {},
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      config: { headers: {} } as any,
      statusText: 'Unprocessable Entity',
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } as any
    mockRegister.mockRejectedValue(axiosError)

    const result = await registerByCredentials(validCredentials)

    expect(result.isSuccess).toBe(false)
    expect(result.message).toContain('ユーザー名は必須です')
    expect(result.message).toContain('このメールアドレスは既に使用されています')
  })

  it('ネットワークエラー時にisSuccess: falseの汎用メッセージを返すこと', async () => {
    const axiosError = new axios.AxiosError('Network Error')
    // response が undefined = ネットワーク障害
    mockRegister.mockRejectedValue(axiosError)

    const result = await registerByCredentials(validCredentials)

    expect(result.isSuccess).toBe(false)
    expect(result.message).toBeTruthy()
  })

  it('Axios以外の例外時にisSuccess: falseの汎用メッセージを返すこと', async () => {
    mockRegister.mockRejectedValue(new Error('予期しないエラー'))

    const result = await registerByCredentials(validCredentials)

    expect(result.isSuccess).toBe(false)
    expect(result.message).toBeTruthy()
  })
})
