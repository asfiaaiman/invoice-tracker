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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agencies', href: '/agencies' },
    { title: 'Create', href: '/agencies/create' },
];

const form = useForm({
    name: '',
    tax_id: '',
    address: '',
    city: '',
    zip_code: '',
    country: 'Serbia',
    email: '',
    phone: '',
    website: '',
    invoice_number_prefix: 'INV',
    is_active: true,
});

function submit() {
    form.post('/agencies');
}
</script>

<template>
    <Head title="Create Agency" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Create Agency</h1>

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

                <div>
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" v-model="form.email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div>
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" />
                    <InputError :message="form.errors.phone" />
                </div>

                <div>
                    <Label for="website">Website</Label>
                    <Input id="website" type="url" v-model="form.website" />
                    <InputError :message="form.errors.website" />
                </div>

                <div>
                    <Label for="invoice_number_prefix">Invoice Number Prefix</Label>
                    <Input id="invoice_number_prefix" v-model="form.invoice_number_prefix" placeholder="INV" />
                    <InputError :message="form.errors.invoice_number_prefix" />
                    <p class="text-sm text-gray-500 mt-1">Used for generating invoice numbers (e.g., INV-2025-0001)</p>
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
                    <Button type="submit" :disabled="form.processing">Create</Button>
                    <Button type="button" variant="outline" @click="$inertia.visit('/agencies')">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

