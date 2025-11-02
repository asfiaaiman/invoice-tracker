<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { reactiveOmit } from '@vueuse/core'
import { SelectTrigger as SelectTriggerPrimitive, type SelectTriggerProps, useForwardProps } from 'reka-ui'

const props = withDefaults(defineProps<SelectTriggerProps & {
  class?: HTMLAttributes['class']
}>(), {})

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectTriggerPrimitive
    data-slot="select-trigger"
    v-bind="forwardedProps"
    :class="cn(
      'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer',
      props.class
    )"
    style="pointer-events: auto !important;"
  >
    <slot />
  </SelectTriggerPrimitive>
</template>

