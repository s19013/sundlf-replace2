<script setup lang="ts">
import { RouterView } from 'vue-router'
import { originalFooter } from '@/shared/ui'
</script>

<template>
  <main>
    <RouterView />
  </main>
  <originalFooter />
</template>

<style scoped></style>
