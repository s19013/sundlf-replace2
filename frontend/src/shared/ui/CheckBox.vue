<script setup lang="ts">
import { computed, useId } from 'vue'
import { Checkbox } from 'primevue'
// 勝手に一番上のdivにもattributeが付くのを防止
defineOptions({ inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    label: string
    id?: string
    fluid?: boolean | null
  }>(),
  {
    id: undefined,
    fluid: true,
  },
)

const generatedId = useId()
const inputId = computed(() => props.id ?? generatedId)
</script>

<template>
  <div :class="{ fluid: fluid }">
    <Checkbox size="large" :inputId="inputId" v-bind="$attrs" binary />
    <label :for="inputId">{{ label }}</label>
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
