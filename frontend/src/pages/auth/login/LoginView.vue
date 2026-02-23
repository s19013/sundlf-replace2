<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { InputText, Password, Button, Message } from 'primevue'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { AuthLayout } from '@/shared/layout'
import { loginByCredentials } from '@/entities/auth/model/loginByCredentials'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const errorMessage = ref<string | null>(null)

async function handleLogin() {
  errorMessage.value = null
  const result = await loginByCredentials({ email: email.value, password: password.value })
  if (result.isSuccess) {
    router.push({ name: 'about' })
  } else {
    errorMessage.value = result.message
  }
}
</script>

<template>
  <AuthLayout>
    <template v-slot:label> ログイン </template>

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
  </AuthLayout>
</template>

<style scoped>
.field {
  margin-bottom: 1.25rem;
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}
</style>
