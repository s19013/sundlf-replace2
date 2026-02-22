<script setup lang="ts">
import { Button } from 'primevue'

defineProps({
  icon: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: null,
  },
  gap: {
    type: Number,
    default: 1,
  },
})
</script>

<template>
  <Button>
    <div :class="`flex gap-${gap}`">
      <SvgIcon type="mdi" :path="icon" />
      <p>{{ label }}</p>
    </div>
  </Button>
</template>
