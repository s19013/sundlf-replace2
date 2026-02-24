<script setup lang="ts">
import { Checkbox } from 'primevue'
// 勝手に一番上のdivにもattributeが付くのを防止
defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    label: string
    id?: string | undefined
    fluid?: boolean | null
  }>(),
  {
    id: undefined,
    fluid: true,
  },
)
</script>

<template>
  <div :class="{ fluid: fluid }">
    <Checkbox size="large" :inputId="id" v-bind="$attrs" binary />
    <label :for="id">{{ label }}</label>
  </div>
</template>

<style scoped>
div {
  margin-bottom: 1.25rem;
  display: flex;
  gap: 1rem;
}

.fluid {
  width: 100%;
}
</style>
