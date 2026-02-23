<script setup lang="ts">
import { Button } from 'primevue'

withDefaults(
  defineProps<{
    icon?: string | null
    label?: string | null
    gap?: number
  }>(),
  {
    icon: null,
    label: null,
    gap: 1,
  },
)
</script>

<template>
  <Button>
    <div class="flex" :style="{ gap: `${gap}rem` }">
      <SvgIcon type="mdi" :path="icon" />
      <span> {{ label }} </span>
    </div>
  </Button>
</template>
