<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

interface PageProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

const flash = computed(() => {
    const props = page.props as PageProps;
    return props.flash;
});

const hasMessage = computed(() => {
    return !!(flash.value?.success || flash.value?.error);
});

const messageType = computed(() => {
    if (flash.value?.success) return 'success';
    if (flash.value?.error) return 'error';
    return null;
});

const message = computed(() => {
    return flash.value?.success || flash.value?.error;
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
        <Alert
            v-if="hasMessage"
            :variant="messageType === 'error' ? 'destructive' : 'success'"
            class="mb-6"
        >
            <component :is="messageType === 'error' ? AlertCircle : CheckCircle2" class="size-4" />
            <AlertTitle>
                {{ messageType === 'error' ? 'Error' : 'Success' }}
            </AlertTitle>
            <AlertDescription>
                {{ message }}
            </AlertDescription>
        </Alert>
    </Transition>
</template>

