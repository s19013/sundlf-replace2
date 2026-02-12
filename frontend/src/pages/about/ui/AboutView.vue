<script setup lang="ts">
import Button from 'primevue/button'
import { mdiAccount } from '@mdi/js'
</script>

<template>
  <div class="about">
    <h1>This is an about page</h1>
    <Button severity="success">
      <SvgIcon type="mdi" :path="mdiAccount" />
      Submit
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
