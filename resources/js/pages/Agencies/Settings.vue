<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';

interface Agency {
    id: number;
    name: string;
}

interface Props {
    agency: Agency;
    settings: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agencies', href: '/agencies' },
    { title: props.agency.name, href: `/agencies/${props.agency.id}` },
    { title: 'Settings', href: `/agencies/${props.agency.id}/settings` },
];

const form = useForm({
    settings: props.settings || {},
});

function submit() {
    form.post(`/agencies/${props.agency.id}/settings`);
}
</script>

<template>
    <Head :title="`${agency.name} - Settings`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Agency Settings: {{ agency.name }}</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Configure per-agency settings. These settings are specific to this agency and will override default values.
                    </p>
                </div>

                <div>
                    <Label for="settings_custom_field_1">Custom Setting 1</Label>
                    <Input 
                        id="settings_custom_field_1" 
                        v-model="form.settings.custom_field_1" 
                        placeholder="Enter custom setting value"
                    />
                    <InputError :message="form.errors['settings.custom_field_1']" />
                </div>

                <div>
                    <Label for="settings_custom_field_2">Custom Setting 2</Label>
                    <Input 
                        id="settings_custom_field_2" 
                        v-model="form.settings.custom_field_2" 
                        placeholder="Enter custom setting value"
                    />
                    <InputError :message="form.errors['settings.custom_field_2']" />
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Note:</strong> You can add more custom settings as needed. Settings are stored as key-value pairs specific to this agency.
                    </p>
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">Save Settings</Button>
                    <Link :href="`/agencies/${agency.id}/edit`">
                        <Button type="button" variant="outline">Back to Agency</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

