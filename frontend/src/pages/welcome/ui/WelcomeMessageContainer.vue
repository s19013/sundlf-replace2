<script setup lang="ts">
withDefaults(
  defineProps<{
    icon?: string | null
    title?: string
    message?: string | null
  }>(),
  {
    icon: null,
    message: null,
  },
)
</script>

<template>
  <div class="p-6">
    <div class="flex items-center">
      <template v-if="icon != null">
        <SvgIcon type="mdi" :path="icon" />
      </template>
      <div class="ml-4 leading-7 font-semibold">
        <h2>
          {{ title }}
        </h2>
      </div>
    </div>
    <div class="mt-2 ml-10 text-sm">
      <p>{{ message }}</p>
      <slot name="message"></slot>
    </div>
  </div>
</template>
