<script setup lang="ts"></script>

<template>
  <div class="login-container">
    <img :src="'/sundlf_logo.png'" alt="ロゴ" />
    <div class="login-card">
      <slot />
    </div>
  </div>
</template>

<style>
img {
  width: 30%;
}

.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  flex-direction: column;
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

.field {
  margin-bottom: 1.25rem;
}

.field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

@media (max-width: 480px) {
  img {
    width: 100%;
  }
}
</style>
