<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { AlertCircle } from 'lucide-vue-next';
import { formatCurrency } from '@/composables/useCurrency';

interface Props {
    report: {
        agency: {
            id: number;
            name: string;
        };
        current_year_total: number;
        last_365_days_total: number;
        vat_threshold: number;
        remaining_amount: number;
        client_structure: Array<{
            client_id: number;
            client_name: string;
            total: number;
            percentage: number;
            count?: number;
        }>;
        warnings: Array<{
            type: string;
            message: string;
            severity: string;
        }>;
        period_start: string;
        period_end: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: props.report.agency.name, href: `/reports/${props.report.agency.id}` },
];
</script>

<template>
    <Head :title="`Report - ${report.agency.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Agency Report: {{ report.agency.name }}</h1>
                <Link href="/reports">
                    <Button variant="outline">Back to Reports</Button>
                </Link>
            </div>

            <div v-if="report.warnings.length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-100 mb-2">Warnings</h3>
                        <ul class="list-disc list-inside space-y-1 text-red-800 dark:text-red-200">
                            <li v-for="(warning, index) in report.warnings" :key="index">
                                {{ warning.message }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Current Year</h2>
                    <p class="text-sm text-gray-500 mb-3">January 1 - Today</p>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Total Revenue:</span>
                            <span class="font-semibold">{{ formatCurrency(report.current_year_total) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Last 365 Days</h2>
                    <p class="text-sm text-gray-500 mb-3">Rolling 12 months</p>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Total Revenue:</span>
                            <span class="font-semibold">{{ formatCurrency(report.last_365_days_total) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Period:</span>
                            <span>{{ report.period_start }} to {{ report.period_end }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">VAT Threshold</h2>
                    <p class="text-sm text-gray-500 mb-3">Remaining until limit</p>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Threshold:</span>
                            <span>{{ formatCurrency(report.vat_threshold) }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span>Remaining:</span>
                            <span :class="report.remaining_amount < 0 ? 'text-red-600 font-bold' : report.remaining_amount < (report.vat_threshold * 0.1) ? 'text-yellow-600 font-bold' : 'font-semibold'">
                                {{ formatCurrency(report.remaining_amount) }}
                            </span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div
                                    class="h-2.5 rounded-full"
                                    :class="report.remaining_amount < 0 ? 'bg-red-600' : report.remaining_amount < (report.vat_threshold * 0.1) ? 'bg-yellow-600' : 'bg-blue-600'"
                                    :style="{ width: `${Math.min(100, Math.max(0, (report.vat_threshold - report.remaining_amount) / report.vat_threshold * 100))}%` }"
                                ></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ ((report.vat_threshold - report.remaining_amount) / report.vat_threshold * 100).toFixed(1) }}% used
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="report.client_structure.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Client Structure Analysis (Last 365 Days)</h2>
                <p class="text-sm text-gray-600 mb-4">Detailed breakdown of revenue by client with business rule validations</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoices</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Share</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="client in report.client_structure"
                                :key="client.client_id"
                                :class="client.percentage > 70 ? 'bg-red-50 dark:bg-red-900/20' : ''"
                            >
                                <td class="px-6 py-4 whitespace-nowrap font-medium">{{ client.client_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(client.total) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ client.count || 0 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span :class="client.percentage > 70 ? 'text-red-600 font-bold' : ''">
                                            {{ client.percentage.toFixed(2) }}%
                                        </span>
                                        <div class="w-24 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <div
                                                class="h-2 rounded-full"
                                                :class="client.percentage > 70 ? 'bg-red-600' : 'bg-blue-600'"
                                                :style="{ width: `${Math.min(100, client.percentage)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="client.percentage > 70"
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                                    >
                                        Exceeds Limit
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                                    >
                                        OK
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500">
                No invoices found for this agency in the last 365 days.
            </div>
        </div>
    </AppLayout>
</template>

