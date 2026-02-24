<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/entities/auth/model/authStore'
import { MdiButton } from '@/shared/ui'
import { mdiLogin, mdiAccountPlus } from '@mdi/js'
import { useRouter } from 'vue-router'

const { isAuthenticated } = storeToRefs(useAuthStore())
const router = useRouter()
</script>

<template>
  <div class="buttons my-5">
    <template v-if="isAuthenticated"> </template>

    <template v-else>
      <MdiButton
        style="background-color: #eeeeee"
        :icon="mdiAccountPlus"
        label="初めての方はこちら"
        @click="router.push({ name: 'auth.register' })"
      />
      <MdiButton :icon="mdiLogin" label="ログイン" @click="router.push({ name: 'auth.login' })" />
    </template>
  </div>
</template>

<style scoped>
.buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1.5rem;
}

@media (max-width: 480px) {
  .buttons {
    flex-flow: column;
  }
}
</style>
