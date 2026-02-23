<script setup lang="ts">
import { useAuthStore } from '@/entities/auth/model/authStore'
import MdiButton from '@/shared/ui/MdiButton.vue'
import { mdiLogin } from '@mdi/js'
import { useRouter } from 'vue-router'

const router = useRouter()
const { isAuthenticated } = useAuthStore()
</script>

<template>
  <template v-if="isAuthenticated">
    <div class="buttons"></div>
  </template>

  <template v-if="!isAuthenticated">
    <div class="buttons">
      <router-link :to="router.resolve({ name: 'auth.login' })">
        <MdiButton :icon="mdiLogin" label="ログイン" />
      </router-link>
    </div>
  </template>
</template>

<style scoped>
.buttons {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
