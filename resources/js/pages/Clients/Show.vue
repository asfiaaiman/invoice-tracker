<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-vue-next';

interface Props {
    client: {
        id: number;
        name: string;
        tax_id?: string;
        address?: string;
        city?: string;
        zip_code?: string;
        country?: string;
        email?: string;
        phone?: string;
        note?: string;
        agencies?: Array<{ id: number; name: string }>;
        invoices?: Array<{
            id: number;
            invoice_number: string;
            issue_date: string;
            total: number;
            agency: { name: string };
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
];
</script>

<template>
    <Head :title="`Client - ${client.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">{{ client.name }}</h1>
                <Link :href="`/clients/${client.id}/edit`">
                    <Button variant="outline">
                        <Edit class="w-4 h-4 mr-2" />
                        Edit
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Client Information</h2>
                    <div class="space-y-2">
                        <div>
                            <span class="text-gray-500">Tax ID:</span>
                            <span class="ml-2">{{ client.tax_id || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Email:</span>
                            <span class="ml-2">{{ client.email || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Phone:</span>
                            <span class="ml-2">{{ client.phone || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Address:</span>
                            <span class="ml-2">{{ client.address || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">City:</span>
                            <span class="ml-2">{{ client.city || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Country:</span>
                            <span class="ml-2">{{ client.country || '-' }}</span>
                        </div>
                        <div v-if="client.note">
                            <span class="text-gray-500">Note:</span>
                            <p class="ml-2">{{ client.note }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Agencies</h2>
                    <div v-if="client.agencies && client.agencies.length > 0" class="space-y-2">
                        <div v-for="agency in client.agencies" :key="agency.id">
                            <Link :href="`/agencies/${agency.id}`" class="text-blue-600 hover:underline">
                                {{ agency.name }}
                            </Link>
                        </div>
                    </div>
                    <div v-else class="text-gray-500">No agencies assigned</div>
                </div>
            </div>

            <div v-if="client.invoices && client.invoices.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Invoices</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="invoice in client.invoices" :key="invoice.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="`/invoices/${invoice.id}`" class="text-blue-600 hover:underline">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ invoice.agency.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ invoice.issue_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ invoice.total.toFixed(2) }} RSD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

