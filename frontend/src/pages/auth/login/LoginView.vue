<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { InputText, Password, Message } from 'primevue'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { AuthLayout } from '@/shared/layout'
import { loginByCredentials } from '@/entities/auth/model/loginByCredentials'
import { MdiButton, CheckBox } from '@/shared/ui'
import { mdiLogin } from '@mdi/js'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)
const errorMessage = ref<string | null>(null)

async function handleLogin() {
  errorMessage.value = null
  const result = await loginByCredentials({
    email: email.value,
    password: password.value,
    remember: remember.value,
  })
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

    <Message class="mb-2" v-if="errorMessage" severity="error" :closable="false">
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
        <Password
          input-id="password"
          v-model="password"
          :feedback="false"
          toggle-mask
          required
          fluid
          :inputProps="{ minlength: 8 }"
        />
      </div>

      <CheckBox label="ログイン状態を保持する" id="remember" v-model="remember" />

      <MdiButton
        :icon="mdiLogin"
        label="ログイン"
        type="submit"
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
