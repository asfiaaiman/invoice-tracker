<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    errors: Record<string, string | string[]>;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Please fix the following errors:',
});

const errorMessages = computed(() => {
    const messages: string[] = [];
    
    for (const [field, error] of Object.entries(props.errors)) {
        if (Array.isArray(error)) {
            messages.push(...error);
        } else {
            messages.push(error);
        }
    }
    
    return Array.from(new Set(messages));
});

const hasErrors = computed(() => {
    return Object.keys(props.errors).length > 0;
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform -translate-y-2"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <Alert v-if="hasErrors" variant="destructive" class="mb-6">
            <AlertCircle class="size-4" />
            <AlertTitle>{{ title }}</AlertTitle>
            <AlertDescription>
                <ul class="list-inside list-disc text-sm space-y-1">
                    <li v-for="(message, index) in errorMessages" :key="index">
                        {{ message }}
                    </li>
                </ul>
            </AlertDescription>
        </Alert>
    </Transition>
</template>

