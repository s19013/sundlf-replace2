<script setup lang="ts">
import { useAuthStore } from '@/entities/auth/model/authStore'

const { isAuthenticated } = useAuthStore()
</script>

<template>
  <div class="authMenu">
    <template v-if="isAuthenticated"> </template>

    <template v-else>
      <router-link class="a-blue" :to="{ name: 'auth.register' }"> 初めての方はこちら</router-link>
      <router-link class="a-blue" :to="{ name: 'auth.login' }"> ログイン </router-link>
    </template>
  </div>
</template>

<style>
.authMenu {
  width: 100%;
  display: flex;
  justify-content: flex-start;
  gap: 1.5rem;
  margin: 1rem;
}
</style>
