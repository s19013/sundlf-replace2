<script setup lang="ts">
import ButtonsComponent from './ui/ButtonsComponent.vue'
import WelcomeMessages from './ui/WelcomeMessages.vue'
</script>

<template>
  <img class="logo" src="/sundlf_logo.png" alt="ロゴ" />
  <div class="mt-8 overflow-hidden shadow sm:rounded-lg">
    <h3 class="p-3">
      メモ､ブックマークにタグを付けて保存して整理､検索などで探しやすくするツールアプリです
    </h3>
    <WelcomeMessages />
  </div>
  <ButtonsComponent />
</template>

<style scoped>
.logo {
  width: 25rem;
}
@media (max-width: 480px) {
  .logo {
    width: 100%;
  }
}
</style>
