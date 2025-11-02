<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { reactiveOmit } from '@vueuse/core'
import { SelectValue as SelectValuePrimitive, type SelectValueProps, useForwardProps } from 'reka-ui'

const props = defineProps<SelectValueProps & {
  class?: HTMLAttributes['class']
  placeholder?: string
}>()

const delegatedProps = reactiveOmit(props, 'class', 'placeholder')

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectValuePrimitive
    data-slot="select-value"
    v-bind="forwardedProps"
    :placeholder="placeholder"
    :class="cn(props.class)"
  >
    <slot />
  </SelectValuePrimitive>
</template>

