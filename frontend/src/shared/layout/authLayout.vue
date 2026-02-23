<script setup lang="ts"></script>

<template>
  <img class="logo" src="/sundlf_logo.png" alt="ロゴ" />
  <div class="login-card">
    <h1><slot name="label" /></h1>
    <slot />
  </div>
</template>

<style scoped>
.logo {
  width: 25rem;
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
