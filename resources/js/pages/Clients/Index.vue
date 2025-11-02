<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';

interface Props {
    clients: {
        data: Array<{
            id: number;
            name: string;
            tax_id?: string;
            email?: string;
            agencies?: Array<{ id: number; name: string }>;
        }>;
        links: any;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
];

function deleteClient(id: number) {
    if (confirm('Are you sure you want to delete this client?')) {
        router.delete(`/clients/${id}`);
    }
}
</script>

<template>
    <Head title="Clients" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Clients</h1>
                <Link href="/clients/create">
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        New Client
                    </Button>
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agencies</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">{{ client.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ client.tax_id || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ client.email || '-' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="client.agencies && client.agencies.length > 0">
                                    {{ client.agencies.map(a => a.name).join(', ') }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link :href="`/clients/${client.id}/edit`" class="mr-2">
                                    <Button variant="outline" size="sm">
                                        <Edit class="w-4 h-4" />
                                    </Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="deleteClient(client.id)">
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

