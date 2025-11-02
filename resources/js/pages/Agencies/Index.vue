<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { Plus, Edit, Trash2, Settings, Power } from 'lucide-vue-next';

interface Props {
    agencies: {
        data: Array<{
            id: number;
            name: string;
            tax_id?: string;
            city?: string;
            email?: string;
            phone?: string;
            is_active?: boolean;
        }>;
        links: any;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agencies', href: '/agencies' },
];

function deleteAgency(id: number) {
    if (confirm('Are you sure you want to delete this agency?')) {
        router.delete(`/agencies/${id}`);
    }
}

function toggleStatus(id: number) {
    router.post(`/agencies/${id}/toggle-status`, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Agencies" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Agencies</h1>
                <Link href="/agencies/create">
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        New Agency
                    </Button>
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="agency in agencies.data" :key="agency.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ agency.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ agency.tax_id || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div v-if="agency.email" class="text-gray-900 dark:text-gray-100">{{ agency.email }}</div>
                                    <div v-if="agency.phone" class="text-gray-500 dark:text-gray-400">{{ agency.phone }}</div>
                                    <div v-if="!agency.email && !agency.phone" class="text-gray-400">-</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ agency.city || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        agency.is_active 
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    {{ agency.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/agencies/${agency.id}/settings`">
                                        <Button variant="outline" size="sm" title="Settings">
                                            <Settings class="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        :title="agency.is_active ? 'Deactivate' : 'Activate'"
                                        @click="toggleStatus(agency.id)"
                                    >
                                        <Power class="w-4 h-4" :class="{ 'text-green-600': !agency.is_active, 'text-gray-600': agency.is_active }" />
                                    </Button>
                                    <Link :href="`/agencies/${agency.id}/edit`">
                                        <Button variant="outline" size="sm" title="Edit">
                                            <Edit class="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="destructive" size="sm" @click="deleteAgency(agency.id)" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

