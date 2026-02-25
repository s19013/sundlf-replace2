<script setup lang="ts">
import { computed, useId } from 'vue'
import { Checkbox } from 'primevue'
// 勝手に一番上のdivにもattributeが付くのを防止
defineOptions({ inheritAttrs: false })

// note:公式の型自体がこうなっている
type Size = 'small' | 'large' | undefined

const props = withDefaults(
  defineProps<{
    label: string
    id?: string
    fluid?: boolean | null
    size?: Size
  }>(),
  {
    id: undefined,
    fluid: true,
    size: undefined,
  },
)

const generatedId = useId()
const inputId = computed(() => props.id ?? generatedId)
</script>

<template>
  <div class="mb-5 flex items-center gap-2" :class="{ fluid: fluid }">
    <Checkbox :size="size" :inputId="inputId" v-bind="$attrs" binary />
    <label :for="inputId">{{ label }}</label>
  </div>
</template>

<style scoped>
.fluid {
  width: 100%;
}
</style>
