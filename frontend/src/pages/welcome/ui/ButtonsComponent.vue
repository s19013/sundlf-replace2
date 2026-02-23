<script setup lang="ts">
import { useAuthStore } from '@/entities/auth/model/authStore'
import { MdiButton } from '@/shared/ui'
import { mdiLogin } from '@mdi/js'

const { isAuthenticated } = useAuthStore()
</script>

<template>
  <div class="my-5">
    <template v-if="isAuthenticated">
      <div class="buttons"></div>
    </template>

    <template v-else>
      <div class="buttons">
        <router-link :to="{ name: 'auth.login' }">
          <MdiButton :icon="mdiLogin" label="ログイン" />
        </router-link>
      </div>
    </template>
  </div>
</template>

<style scoped>
.buttons {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
