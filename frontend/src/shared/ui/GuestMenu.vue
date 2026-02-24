<script setup lang="ts">
import { useAuthStore } from '@/entities/auth/model/authStore'

const authStore = useAuthStore()
const isAuthenticated = authStore.isAuthenticated
</script>

<template>
  <div class="authMenu">
    <router-link class="a-blue" :to="{ name: 'welcome' }"> TOP </router-link>
    <template v-if="isAuthenticated"> </template>

    <template v-else>
      <router-link class="a-blue" :to="{ name: 'auth.register' }"> 初めての方はこちら</router-link>
      <router-link class="a-blue" :to="{ name: 'auth.login' }"> ログイン </router-link>
    </template>
  </div>
</template>

<style scoped>
.authMenu {
  width: 100%;
  display: flex;
  justify-content: flex-start;
  gap: 1.5rem;
  margin: 1rem;
}
</style>
