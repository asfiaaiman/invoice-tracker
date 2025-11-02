<script setup lang="ts">
import { SelectRoot as SelectRootPrimitive, type SelectRootEmits, type SelectRootProps, useForwardPropsEmits } from 'reka-ui'
import { type HTMLAttributes } from 'vue'

interface Props extends SelectRootProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {})
const emits = defineEmits<SelectRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <SelectRootPrimitive
    v-bind="forwarded"
  >
    <slot />
  </SelectRootPrimitive>
</template>

