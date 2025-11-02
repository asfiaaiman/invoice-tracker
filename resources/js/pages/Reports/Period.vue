<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashMessage from '@/components/FlashMessage.vue';
import { formatCurrency } from '@/composables/useCurrency';
import { reactive, ref } from 'vue';

interface Agency {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    invoice_number: string;
    issue_date: string;
    total: number;
    client: {
        name: string;
    };
    agency: {
        name: string;
    };
}

interface ClientSummary {
    client_id: number;
    client_name: string;
    total: number;
    percentage: number;
    invoice_count: number;
}

interface Props {
    agencies: Agency[];
    invoices: Invoice[];
    totalAmount: number;
    clientSummary: ClientSummary[];
    filters: {
        agency_id?: number;
        start_date?: string;
        end_date?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Report by Period', href: '/reports/period' },
];

const filters = reactive({
    agency_id: props.filters.agency_id ? Number(props.filters.agency_id) : null,
    agency_id_string: props.filters.agency_id ? String(props.filters.agency_id) : null,
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const selectOpen = ref(false);

// Debug: Watch for open state changes
import { watch } from 'vue';
watch(selectOpen, (newVal) => {
    console.log('[Select] Open state changed:', newVal);
}, { immediate: true });

function applyPreset(preset: string) {
    const today = new Date();
    const startOfYear = new Date(today.getFullYear(), 0, 1);

    switch (preset) {
        case 'this_year':
            filters.start_date = startOfYear.toISOString().split('T')[0];
            filters.end_date = today.toISOString().split('T')[0];
            break;
        case 'last_30_days':
            const last30Days = new Date(today);
            last30Days.setDate(last30Days.getDate() - 30);
            filters.start_date = last30Days.toISOString().split('T')[0];
            filters.end_date = today.toISOString().split('T')[0];
            break;
        case 'last_365_days':
            const last365Days = new Date(today);
            last365Days.setDate(last365Days.getDate() - 365);
            filters.start_date = last365Days.toISOString().split('T')[0];
            filters.end_date = today.toISOString().split('T')[0];
            break;
    }
    applyFilters();
}

function applyFilters() {
    router.get('/reports/period', {
        agency_id: filters.agency_id,
        start_date: filters.start_date,
        end_date: filters.end_date,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Report by Period" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Report by Period</h1>

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <Label for="agency_id">Agency</Label>
                        <Select
                            v-model:open="selectOpen"
                            @update:model-value="(val: string | number | null | undefined) => {
                                console.log('Select value changed:', val, typeof val);
                                if (val && val !== '' && val !== null && val !== undefined) {
                                    filters.agency_id = typeof val === 'number' ? val : parseInt(String(val));
                                    filters.agency_id_string = String(val);
                                } else {
                                    filters.agency_id = null;
                                    filters.agency_id_string = null;
                                }
                            }"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select agency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="agency in agencies"
                                    :key="`agency-${agency.id}`"
                                    :value="String(agency.id)"
                                >
                                    {{ agency.name }}
                                </SelectItem>
                                <SelectItem v-if="agencies.length === 0" value="none" disabled>
                                    No agencies available
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="start_date">Start Date</Label>
                        <Input
                            id="start_date"
                            type="date"
                            v-model="filters.start_date"
                        />
                    </div>

                    <div>
                        <Label for="end_date">End Date</Label>
                        <Input
                            id="end_date"
                            type="date"
                            v-model="filters.end_date"
                        />
                    </div>

                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters" class="w-full">Apply Filters</Button>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="applyPreset('this_year')">
                        This Year
                    </Button>
                    <Button variant="outline" size="sm" @click="applyPreset('last_30_days')">
                        Last 30 Days
                    </Button>
                    <Button variant="outline" size="sm" @click="applyPreset('last_365_days')">
                        Last 365 Days
                    </Button>
                </div>
            </div>

            <div v-if="filters.agency_id && filters.start_date && filters.end_date" class="space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Summary</h2>
                    <p class="text-lg">
                        <strong>Total Amount:</strong> {{ formatCurrency(totalAmount) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ invoices.length }} invoice(s) in period
                    </p>
                </div>

                <div v-if="clientSummary.length > 0" class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">By Client</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoices</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="client in clientSummary"
                                    :key="client.client_id"
                                    :class="{ 'bg-red-50': client.percentage > 70 }"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">{{ client.client_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(client.total) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{ 'text-red-600 font-bold': client.percentage > 70 }">
                                            {{ client.percentage.toFixed(2) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ client.invoice_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="invoices.length > 0" class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Invoices</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="invoice in invoices" :key="invoice.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ invoice.invoice_number }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ invoice.issue_date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ invoice.client.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(invoice.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                    No invoices found for the selected period.
                </div>
            </div>

            <div v-else class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                <p>Please select an agency and date range to view the report.</p>
                <p v-if="agencies.length === 0" class="mt-2 text-sm text-red-500">
                    No active agencies available. Please create or activate an agency first.
                </p>
                <p v-else class="mt-2 text-sm">
                    {{ agencies.length }} active agency(ies) available.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

