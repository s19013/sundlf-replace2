<script setup lang="ts"></script>

<template>
  <div class="login-container">
    <img class="logo" src="/sundlf_logo.png" alt="ロゴ" />
    <div class="login-card">
      <h1><slot name="label" /></h1>
      <slot />
    </div>
  </div>
</template>

<style scoped>
.logo {
  width: 25rem;
}

.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  min-height: calc(
    100dvh - var(--main-padding-vertical) * 2
  ); /* main の padding 上下分を差し引く */
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
}

.login-card h1 {
  text-align: center;
  margin-bottom: 1.5rem;
}

@media (max-width: 480px) {
  .logo {
    width: 100%;
  }
}
</style>
