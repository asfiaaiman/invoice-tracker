<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { formatCurrency } from '@/composables/useCurrency';
import {
    Building2,
    Users,
    Package,
    FileText,
    TrendingUp,
    DollarSign,
} from 'lucide-vue-next';

interface Props {
    stats: {
        agencies: {
            total: number;
            href: string;
        };
        clients: {
            total: number;
            href: string;
        };
        products: {
            total: number;
            href: string;
        };
        invoices: {
            total: number;
            thisMonth: number;
            trend: number;
            href: string;
        };
        revenue: {
            total: number;
            thisMonth: number;
            thisYear: number;
            last365Days?: number;
            trend: number;
        };
    };
    recentInvoices: Array<{
        id: number;
        invoice_number: string;
        agency_name: string;
        client_name: string;
        total: number;
        issue_date: string;
    }>;
    monthlyRevenue: Array<{
        month: number;
        revenue: number;
    }>;
    agencyStats?: Array<{
        id: number;
        name: string;
        invoice_count: number;
        current_year_revenue: number;
        last_365_days_revenue: number;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const monthNames = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    :title="'Agencies'"
                    :value="stats.agencies.total"
                    :icon="Building2"
                    :href="stats.agencies.href"
                    :description="'Active agencies'"
                />
                <StatCard
                    :title="'Clients'"
                    :value="stats.clients.total"
                    :icon="Users"
                    :href="stats.clients.href"
                    :description="'Total clients'"
                />
                <StatCard
                    :title="'Products'"
                    :value="stats.products.total"
                    :icon="Package"
                    :href="stats.products.href"
                    :description="'Total products'"
                />
                <StatCard
                    :title="'Total Invoices'"
                    :value="stats.invoices.total"
                    :icon="FileText"
                    :href="stats.invoices.href"
                    :description="`${stats.invoices.thisMonth} this month`"
                    :trend="{
                        value: stats.invoices.trend,
                        label: 'vs last month',
                        isPositive: stats.invoices.trend >= 0,
                    }"
                />
                <StatCard
                    :title="'Total Revenue'"
                    :value="formatCurrency(stats.revenue.total)"
                    :icon="DollarSign"
                    :description="`${formatCurrency(stats.revenue.thisYear)} this year`"
                />
                <StatCard
                    v-if="stats.revenue.last365Days !== undefined"
                    :title="'Last 365 Days Revenue'"
                    :value="formatCurrency(stats.revenue.last365Days)"
                    :icon="DollarSign"
                    :description="'Rolling 12 months'"
                />
                <StatCard
                    :title="'Monthly Revenue'"
                    :value="formatCurrency(stats.revenue.thisMonth)"
                    :icon="TrendingUp"
                    :description="'Current month'"
                    :trend="{
                        value: stats.revenue.trend,
                        label: 'vs last month',
                        isPositive: stats.revenue.trend >= 0,
                    }"
                />
            </div>

            <div v-if="agencyStats && agencyStats.length > 0" class="grid gap-4 md:grid-cols-1">
                <Card>
                    <CardHeader>
                        <CardTitle>Agency Revenue Overview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agency</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoices</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last 365 Days</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="agency in agencyStats" :key="agency.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link :href="`/reports/${agency.id}`" class="font-medium hover:underline">
                                                {{ agency.name }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ agency.invoice_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(agency.current_year_revenue) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(agency.last_365_days_revenue) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link :href="`/reports/${agency.id}`">
                                                <Button variant="outline" size="sm">View Report</Button>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Invoices</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentInvoices.length === 0" class="text-sm text-muted-foreground py-4">
                            No invoices found
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="invoice in recentInvoices"
                                :key="invoice.id"
                                class="flex items-center justify-between border-b pb-3 last:border-0"
                            >
                                <div class="flex-1">
                                    <Link
                                        :href="`/invoices/${invoice.id}`"
                                        class="font-medium hover:underline"
                >
                                        {{ invoice.invoice_number }}
                                    </Link>
                                    <p class="text-sm text-muted-foreground">
                                        {{ invoice.agency_name }} → {{ invoice.client_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ invoice.issue_date }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold">{{ formatCurrency(invoice.total) }}</p>
                                </div>
                </div>
                            <Link
                                href="/invoices"
                                class="block text-center text-sm text-muted-foreground hover:text-foreground mt-4"
                            >
                                View all invoices →
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Monthly Revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="monthlyRevenue.length === 0" class="text-sm text-muted-foreground py-4">
                            No revenue data for this year
                </div>
                        <div v-else class="space-y-2">
                <div
                                v-for="item in monthlyRevenue"
                                :key="item.month"
                                class="flex items-center justify-between"
                >
                                <span class="text-sm">{{ monthNames[item.month - 1] }}</span>
                                <span class="font-semibold">{{ formatCurrency(item.revenue) }}</span>
                </div>
            </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
