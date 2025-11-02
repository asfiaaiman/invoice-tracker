<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';

interface Props {
    agencies: {
        data: Array<{
            id: number;
            name: string;
            tax_id?: string;
            city?: string;
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="agency in agencies.data" :key="agency.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">{{ agency.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ agency.tax_id || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ agency.city || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link :href="`/agencies/${agency.id}/edit`" class="mr-2">
                                    <Button variant="outline" size="sm">
                                        <Edit class="w-4 h-4" />
                                    </Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="deleteAgency(agency.id)">
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

