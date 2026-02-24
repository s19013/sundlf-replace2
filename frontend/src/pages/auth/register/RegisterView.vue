<script setup lang="ts">
import { ref } from 'vue'
// import { useRouter } from 'vue-router'
import { InputText, Password, Message } from 'primevue'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { AuthLayout } from '@/shared/layout'
import { MdiButton } from '@/shared/ui'
import { mdiAccountPlus } from '@mdi/js'

// const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref<string | null>(null)

async function handleRegister() {
  errorMessage.value = null
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'パスワードが一致しません'
    return
  }
  // todo:登録api呼び出し

  // if (result.isSuccess) {
  //   router.push({ name: 'home' })
  // } else {
  //   errorMessage.value = result.message
  // }
}
</script>

<template>
  <AuthLayout>
    <template v-slot:label> 新規登録 </template>

    <Message class="mb-2" v-if="errorMessage" severity="error" :closable="false">
      {{ errorMessage }}
    </Message>

    <form @submit.prevent="handleRegister">
      <div class="field">
        <label for="name">ユーザー名</label>
        <InputText id="name" v-model="name" type="text" required fluid />
      </div>

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
          v-model="password"
          :feedback="false"
          toggle-mask
          required
          fluid
          :inputProps="{ id: 'password', minlength: 8 }"
        />
      </div>

      <div class="field">
        <label for="password_confirmation">パスワード(再確認)</label>
        <Password
          v-model="passwordConfirmation"
          :feedback="false"
          toggle-mask
          required
          fluid
          :inputProps="{ id: 'password_confirmation', minlength: 8 }"
        />
      </div>

      <MdiButton
        style="background-color: #eeeeee"
        :icon="mdiAccountPlus"
        label="登録"
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
