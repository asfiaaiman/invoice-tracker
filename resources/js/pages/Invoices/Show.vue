<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { Edit, FileText, Trash2 } from 'lucide-vue-next';

interface InvoiceItem {
    id: number;
    product_id?: number;
    product?: {
        id: number;
        name: string;
    };
    description?: string;
    quantity: number;
    unit_price: number;
    total: number;
}

interface Props {
    invoice: {
        id: number;
        invoice_number: string;
        issue_date: string;
        due_date?: string;
        subtotal: number;
        tax_amount: number;
        total: number;
        notes?: string;
        agency: {
            id: number;
            name: string;
            tax_id?: string;
            address?: string;
            city?: string;
            zip_code?: string;
            country?: string;
        };
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
        };
        items: InvoiceItem[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/invoices' },
    { title: props.invoice.invoice_number, href: `/invoices/${props.invoice.id}` },
];
</script>

<template>
    <Head :title="`Invoice - ${invoice.invoice_number}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Invoice {{ invoice.invoice_number }}</h1>
                <div class="flex gap-2">
                    <a :href="`/invoices/${invoice.id}/pdf`" target="_blank" class="inline-block">
                        <Button variant="outline">
                            <FileText class="w-4 h-4 mr-2" />
                            PDF
                        </Button>
                    </a>
                    <Link :href="`/invoices/${invoice.id}/edit`">
                        <Button variant="outline">
                            <Edit class="w-4 h-4 mr-2" />
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">From (Agency)</h2>
                    <div class="space-y-2">
                        <div>
                            <span class="font-semibold">{{ invoice.agency.name }}</span>
                        </div>
                        <div v-if="invoice.agency.tax_id">
                            <span class="text-gray-500">Tax ID:</span>
                            <span class="ml-2">{{ invoice.agency.tax_id }}</span>
                        </div>
                        <div v-if="invoice.agency.address">
                            <div>{{ invoice.agency.address }}</div>
                            <div v-if="invoice.agency.city">
                                {{ invoice.agency.city }}{{ invoice.agency.zip_code ? `, ${invoice.agency.zip_code}` : '' }}
                            </div>
                            <div v-if="invoice.agency.country">{{ invoice.agency.country }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">To (Client)</h2>
                    <div class="space-y-2">
                        <div>
                            <span class="font-semibold">{{ invoice.client.name }}</span>
                        </div>
                        <div v-if="invoice.client.tax_id">
                            <span class="text-gray-500">Tax ID:</span>
                            <span class="ml-2">{{ invoice.client.tax_id }}</span>
                        </div>
                        <div v-if="invoice.client.address">
                            <div>{{ invoice.client.address }}</div>
                            <div v-if="invoice.client.city">
                                {{ invoice.client.city }}{{ invoice.client.zip_code ? `, ${invoice.client.zip_code}` : '' }}
                            </div>
                            <div v-if="invoice.client.country">{{ invoice.client.country }}</div>
                        </div>
                        <div v-if="invoice.client.email">
                            <span class="text-gray-500">Email:</span>
                            <span class="ml-2">{{ invoice.client.email }}</span>
                        </div>
                        <div v-if="invoice.client.phone">
                            <span class="text-gray-500">Phone:</span>
                            <span class="ml-2">{{ invoice.client.phone }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <span class="text-gray-500 text-sm">Invoice Number</span>
                        <div class="font-semibold">{{ invoice.invoice_number }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm">Issue Date</span>
                        <div class="font-semibold">{{ invoice.issue_date }}</div>
                    </div>
                    <div v-if="invoice.due_date">
                        <span class="text-gray-500 text-sm">Due Date</span>
                        <div class="font-semibold">{{ invoice.due_date }}</div>
                    </div>
                </div>

                <h2 class="text-lg font-semibold mb-4">Items</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product/Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="px-6 py-4">
                                    <div v-if="item.product">
                                        <div class="font-semibold">{{ item.product.name }}</div>
                                        <div v-if="item.description" class="text-sm text-gray-500">{{ item.description }}</div>
                                    </div>
                                    <div v-else-if="item.description">{{ item.description }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-right">{{ Number(item.unit_price || 0).toFixed(2) }} RSD</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ Number(item.total || 0).toFixed(2) }} RSD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <div class="w-full md:w-1/3">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Subtotal:</span>
                                <span class="font-semibold">{{ Number(invoice.subtotal || 0).toFixed(2) }} RSD</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tax (20%):</span>
                                <span class="font-semibold">{{ Number(invoice.tax_amount || 0).toFixed(2) }} RSD</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-lg font-semibold">Total:</span>
                                <span class="text-lg font-bold">{{ Number(invoice.total || 0).toFixed(2) }} RSD</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="invoice.notes" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ invoice.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

