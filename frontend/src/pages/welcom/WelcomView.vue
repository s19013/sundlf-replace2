<script setup lang="ts">
import WelcomMessages from './ui/WelcomMessages.vue'
</script>

<template>
  <img class="logo" src="/sundlf_logo.png" alt="ロゴ" />
  <div class="mt-8 overflow-hidden shadow sm:rounded-lg">
    <h3 class="p-3">
      メモ､ブックマークにタグを付けて保存して整理､検索などで探しやすくするツールアプリです
    </h3>
    <WelcomMessages />
  </div>
</template>

<style scoped>
.logo {
  width: 25rem;
}
</style>
