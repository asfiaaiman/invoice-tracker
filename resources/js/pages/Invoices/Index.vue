<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import FlashMessage from '@/components/FlashMessage.vue';
import { Plus, Download, Edit, Trash2 } from 'lucide-vue-next';

interface Props {
    invoices: any;
    agencies: Array<{ id: number; name: string }>;
    clients: Array<{ id: number; name: string }>;
    filters: {
        agency_id?: number;
        client_id?: number;
        search?: string;
        start_date?: string;
        end_date?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/invoices' },
];

const filterForm = useForm({
    agency_id: props.filters.agency_id || '',
    client_id: props.filters.client_id || '',
    search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

function applyFilters() {
    filterForm.get('/invoices', {
        preserveState: true,
    });
}

function deleteInvoice(id: number) {
    if (confirm('Are you sure you want to delete this invoice?')) {
        router.delete(`/invoices/${id}`);
    }
}
</script>

<template>
    <Head title="Invoices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Invoices</h1>
                <Link href="/invoices/create">
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        New Invoice
                    </Button>
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-6">
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <select v-model="filterForm.agency_id" class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                            <option value="">All Agencies</option>
                            <option v-for="agency in agencies" :key="agency.id" :value="agency.id">
                                {{ agency.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select v-model="filterForm.client_id" class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                            <option value="">All Clients</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Input v-model="filterForm.search" placeholder="Search..." />
                    </div>
                    <div>
                        <Input v-model="filterForm.start_date" type="date" placeholder="Start Date" />
                    </div>
                    <div>
                        <Input v-model="filterForm.end_date" type="date" placeholder="End Date" />
                    </div>
                    <div class="md:col-span-5">
                        <Button type="submit">Filter</Button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="!invoices?.data || invoices?.data.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No invoices found.</td>
                        </tr>
                        <tr v-for="invoice in invoices?.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">{{ invoice.invoice_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ invoice.agency?.name || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ invoice.client?.name || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ invoice.issue_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ Number(invoice.total || 0).toFixed(2) }} RSD</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <a :href="`/invoices/${invoice.id}/pdf`" target="_blank" class="inline-block">
                                    <Button variant="outline" size="sm">
                                        <Download class="w-4 h-4" />
                                    </Button>
                                </a>
                                <Link :href="`/invoices/${invoice.id}/edit`">
                                    <Button variant="outline" size="sm">
                                        <Edit class="w-4 h-4" />
                                    </Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="deleteInvoice(invoice.id)">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

