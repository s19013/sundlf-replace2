<script setup lang="ts">
import Button from 'primevue/button'
import { useRouter } from 'vue-router'
import { mdiAccount } from '@mdi/js'
import { useAuthStore } from '@/entities/auth/model/authStore'

const router = useRouter()
const { logout } = useAuthStore()

const handleLogout = async () => {
  try {
    await logout()
  } finally {
    router.push({ name: 'login' })
  }
}
</script>

<template>
  <div class="about">
    <h1>This is an about page</h1>
    <Button severity="success" v-on:click="handleLogout">
      <SvgIcon type="mdi" :path="mdiAccount" />
      ログアウト
    </Button>
  </div>
</template>

<style>
@media (min-width: 1024px) {
  .about {
    min-height: 100vh;
    display: flex;
    align-items: center;
  }
}
</style>
