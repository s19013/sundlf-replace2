<script setup lang="ts">
import { useAuthStore } from '@/entities/auth/model/authStore'
import { MdiButton } from '@/shared/ui'
import { mdiLogin, mdiAccountPlus } from '@mdi/js'

const { isAuthenticated } = useAuthStore()
</script>

<template>
  <div class="buttons my-5">
    <template v-if="isAuthenticated"> </template>

    <template v-else>
      <router-link :to="{ name: 'auth.register' }">
        <MdiButton
          style="background-color: #eeeeee"
          :icon="mdiAccountPlus"
          label="初めての方はこちら"
        />
      </router-link>
      <router-link :to="{ name: 'auth.login' }">
        <MdiButton :icon="mdiLogin" label="ログイン" />
      </router-link>
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
