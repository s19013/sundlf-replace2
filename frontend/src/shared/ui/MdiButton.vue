<script setup lang="ts">
import { Button } from 'primevue'

withDefaults(
  defineProps<{
    icon: string
    label?: string | null
    gap?: number
    color?: string | null
  }>(),
  {
    label: null,
    gap: 1,
    color: 'black',
  },
)
</script>

<template>
  <!-- 親から渡された :loading :disable  などを引き継ぐ-->
  <Button v-bind="$attrs">
    <div class="flex" :style="{ gap: `${gap}rem`, color: `${color}` }">
      <SvgIcon type="mdi" :path="icon" />
      <span> {{ label }} </span>
    </div>
  </Button>
</template>
