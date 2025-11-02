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
    settings: {
        pdv_limit: string;
        client_max_share_percent: string;
        min_clients_per_year: string;
        invoice_number_prefix: string;
    };
    defaultSettings: {
        pdv_limit: string;
        client_max_share_percent: string;
        min_clients_per_year: string;
        invoice_number_prefix: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agencies', href: '/agencies' },
    { title: props.agency.name, href: `/agencies/${props.agency.id}` },
    { title: 'Settings', href: `/agencies/${props.agency.id}/settings` },
];

const form = useForm({
    pdv_limit: props.settings?.pdv_limit || props.defaultSettings?.pdv_limit || '',
    client_max_share_percent: props.settings?.client_max_share_percent || props.defaultSettings?.client_max_share_percent || '',
    min_clients_per_year: props.settings?.min_clients_per_year || props.defaultSettings?.min_clients_per_year || '',
    invoice_number_prefix: props.settings?.invoice_number_prefix || props.defaultSettings?.invoice_number_prefix || '',
});

function submit() {
    form.post(`/agencies/${props.agency.id}/settings`);
}
</script>

<template>
    <Head :title="`${agency.name} - Settings`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Agency Settings: {{ agency.name }}</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Configure per-agency settings. These settings are specific to this agency and will override default values. These settings are used for business rule validation in reports.
                    </p>
                </div>

                <div>
                    <Label for="pdv_limit">VAT Threshold (RSD) *</Label>
                    <Input
                        id="pdv_limit"
                        type="number"
                        v-model="form.pdv_limit"
                        :placeholder="defaultSettings?.pdv_limit || '6000000'"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Default: {{ (parseInt(defaultSettings?.pdv_limit || '6000000')).toLocaleString() }} RSD
                    </p>
                    <InputError :message="form.errors.pdv_limit" />
                </div>

                <div>
                    <Label for="client_max_share_percent">Maximum Client Share (%) *</Label>
                    <Input
                        id="client_max_share_percent"
                        type="number"
                        v-model="form.client_max_share_percent"
                        :placeholder="defaultSettings?.client_max_share_percent || '70'"
                        min="0"
                        max="100"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Default: {{ defaultSettings?.client_max_share_percent || '70' }}%
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        No single client may exceed this percentage of the agency's total turnover.
                    </p>
                    <InputError :message="form.errors.client_max_share_percent" />
                </div>

                <div>
                    <Label for="min_clients_per_year">Minimum Clients Per Year *</Label>
                    <Input
                        id="min_clients_per_year"
                        type="number"
                        v-model="form.min_clients_per_year"
                        :placeholder="defaultSettings?.min_clients_per_year || '5'"
                        min="1"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Default: {{ defaultSettings?.min_clients_per_year || '5' }} clients
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Minimum number of different clients that must be invoiced within the measurement period (last 365 days).
                    </p>
                    <InputError :message="form.errors.min_clients_per_year" />
                </div>

                <div>
                    <Label for="invoice_number_prefix">Invoice Number Prefix</Label>
                    <Input
                        id="invoice_number_prefix"
                        type="text"
                        v-model="form.invoice_number_prefix"
                        :placeholder="defaultSettings?.invoice_number_prefix || 'INV'"
                        maxlength="20"
                        class="uppercase"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Default: {{ defaultSettings?.invoice_number_prefix || 'INV' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Prefix used for auto-generated invoice numbers. Format: PREFIX-YYYY-#### (e.g., INV-2024-0001). Only uppercase letters, numbers, hyphens, and underscores allowed.
                    </p>
                    <InputError :message="form.errors.invoice_number_prefix" />
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

