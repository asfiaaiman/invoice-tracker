<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';

interface Props {
    agency: {
        id: number;
        name: string;
        tax_id?: string;
        address?: string;
        city?: string;
        zip_code?: string;
        country?: string;
        is_active?: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agencies', href: '/agencies' },
    { title: 'Edit', href: `/agencies/${props.agency.id}/edit` },
];

const form = useForm({
    name: props.agency.name,
    tax_id: props.agency.tax_id || '',
    address: props.agency.address || '',
    city: props.agency.city || '',
    zip_code: props.agency.zip_code || '',
    country: props.agency.country || 'Serbia',
    is_active: props.agency.is_active ?? true,
});

function submit() {
    form.put(`/agencies/${props.agency.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Message will be shown on the index page after redirect
        },
        onError: () => {
            // Errors will be shown inline via InputError components
        },
    });
}
</script>

<template>
    <Head title="Edit Agency" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Edit Agency</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <Label for="tax_id">Tax ID</Label>
                    <Input id="tax_id" v-model="form.tax_id" />
                    <InputError :message="form.errors.tax_id" />
                </div>

                <div>
                    <Label for="address">Address</Label>
                    <Input id="address" v-model="form.address" />
                    <InputError :message="form.errors.address" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" />
                        <InputError :message="form.errors.city" />
                    </div>
                    <div>
                        <Label for="zip_code">Zip Code</Label>
                        <Input id="zip_code" v-model="form.zip_code" />
                        <InputError :message="form.errors.zip_code" />
                    </div>
                </div>

                <div>
                    <Label for="country">Country</Label>
                    <Input id="country" v-model="form.country" />
                    <InputError :message="form.errors.country" />
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        type="checkbox"
                        v-model="form.is_active"
                        class="rounded border-gray-300"
                    />
                    <Label for="is_active">Active</Label>
                    <InputError :message="form.errors.is_active" />
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">Update</Button>
                    <Button type="button" variant="outline" @click="$inertia.visit('/agencies')">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

