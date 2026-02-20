<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { authLayout } from '@/shared/layout'
import { loginByCredentials } from '@/entities/auth/model/loginByCredentials'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const errorMessage = ref<string | null>('')

async function handleLogin() {
  errorMessage.value = ''
  const result = await loginByCredentials({ email: email.value, password: password.value })
  if (result.isSuccess) {
    router.push({ name: 'home' })
  }

  errorMessage.value = result.message
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
