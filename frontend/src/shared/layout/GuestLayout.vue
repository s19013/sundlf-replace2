<script setup lang="ts">
import { GuestMenu, OriginalFooter } from '@/shared/ui'
</script>

<template>
  <GuestMenu />
  <main>
    <slot></slot>
  </main>
  <OriginalFooter />
</template>
