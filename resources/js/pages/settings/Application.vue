<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';

interface Agency {
    id: number;
    name: string;
}

interface Props {
    agencies: Agency[];
    settings: Record<number, {
        pdv_limit: string;
        client_max_share_percent: string;
        min_clients_per_year: string;
        invoice_number_prefix: string;
    }>;
    defaultSettings: {
        pdv_limit: string;
        client_max_share_percent: string;
        min_clients_per_year: string;
        invoice_number_prefix: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings/application' },
    { title: 'Application Settings', href: '/settings/application' },
];

const form = useForm({
    agency_id: props.agencies[0]?.id || null,
    pdv_limit: '',
    client_max_share_percent: '',
    min_clients_per_year: '',
    invoice_number_prefix: '',
});

function loadAgencySettings(agencyId: number) {
    const settings = props.settings[agencyId] || props.defaultSettings;
    form.agency_id = agencyId;
    form.pdv_limit = settings.pdv_limit;
    form.client_max_share_percent = settings.client_max_share_percent;
    form.min_clients_per_year = settings.min_clients_per_year;
    form.invoice_number_prefix = settings.invoice_number_prefix;
}

function submit() {
    form.post('/settings/application');
}

if (props.agencies.length > 0) {
    loadAgencySettings(props.agencies[0].id);
}
</script>

<template>
    <Head title="Application Settings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Application Settings</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <Label for="agency_id">Agency *</Label>
                    <Select
                        :model-value="form.agency_id?.toString()"
                        @update:model-value="(val) => loadAgencySettings(parseInt(val))"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select agency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="agency in agencies"
                                :key="agency.id"
                                :value="agency.id.toString()"
                            >
                                {{ agency.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="pdv_limit">VAT Threshold (RSD) *</Label>
                    <Input
                        id="pdv_limit"
                        type="number"
                        v-model="form.pdv_limit"
                        placeholder="6000000"
                    />
                    <p class="text-sm text-gray-500 mt-1">Default: 6,000,000 RSD</p>
                </div>

                <div>
                    <Label for="client_max_share_percent">Maximum Client Share (%) *</Label>
                    <Input
                        id="client_max_share_percent"
                        type="number"
                        v-model="form.client_max_share_percent"
                        placeholder="70"
                        min="0"
                        max="100"
                    />
                    <p class="text-sm text-gray-500 mt-1">Default: 70%</p>
                </div>

                <div>
                    <Label for="min_clients_per_year">Minimum Clients Per Year *</Label>
                    <Input
                        id="min_clients_per_year"
                        type="number"
                        v-model="form.min_clients_per_year"
                        placeholder="5"
                        min="1"
                    />
                    <p class="text-sm text-gray-500 mt-1">Default: 5 clients</p>
                </div>

                <div>
                    <Label for="invoice_number_prefix">Invoice Number Prefix</Label>
                    <Input
                        id="invoice_number_prefix"
                        type="text"
                        v-model="form.invoice_number_prefix"
                        placeholder="INV"
                        maxlength="20"
                        class="uppercase"
                        @input="form.invoice_number_prefix = form.invoice_number_prefix.toUpperCase()"
                    />
                    <p class="text-sm text-gray-500 mt-1">Default: INV</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Prefix used for auto-generated invoice numbers. Format: PREFIX-YYYY-#### (e.g., INV-2024-0001). Only uppercase letters, numbers, hyphens, and underscores allowed.
                    </p>
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">Save Settings</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

