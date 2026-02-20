<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { authLayout } from '@/shared/layout'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const errorMessage = ref('')

const GENERIC_ERROR_MESSAGE = 'ログインに失敗しました。しばらくしてからお試しください。'

async function handleLogin() {
  errorMessage.value = ''
  try {
    await authStore.login({ email: email.value, password: password.value })
    router.push({ name: 'home' })
  } catch (error: unknown) {
    if (axios.isAxiosError<{ errors?: Record<string, string[]> }>(error)) {
      if (error.response?.status === 401) {
        errorMessage.value = 'メールアドレスまたはパスワードが正しくありません。'
      } else if (error.response?.status === 422 && error.response.data?.errors) {
        // 今は仮だが、本組は配列でエラーメッセージが帰ってくる予定
        errorMessage.value = Object.values(error.response.data.errors).flat().join('\n')
      } else {
        errorMessage.value = GENERIC_ERROR_MESSAGE
      }
    } else {
      errorMessage.value = GENERIC_ERROR_MESSAGE
    }
  }
}
</script>

<template>
  <authLayout>
    <h1>ログイン</h1>

    <Message v-if="errorMessage" severity="error" :closable="false">
      {{ errorMessage }}
    </Message>

    <form @submit.prevent="handleLogin">
      <div class="field">
        <label for="email">メールアドレス</label>
        <InputText
          id="email"
          v-model="email"
          type="email"
          placeholder="example@mail.com"
          required
          fluid
        />
      </div>

      <div class="field">
        <label for="password">パスワード</label>
        <Password id="password" v-model="password" :feedback="false" toggle-mask required fluid />
      </div>

      <Button
        type="submit"
        label="ログイン"
        :loading="authStore.isLoading"
        :disabled="authStore.isLoading"
        fluid
      />
    </form>
  </authLayout>
</template>
