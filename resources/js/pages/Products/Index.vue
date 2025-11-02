<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';

interface Props {
    products: {
        data: Array<{
            id: number;
            name: string;
            price: number;
            unit?: string;
            agencies?: Array<{ id: number; name: string }>;
        }>;
        links: any;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
];

function deleteProduct(id: number) {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/products/${id}`);
    }
}
</script>

<template>
    <Head title="Products" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Products</h1>
                <Link href="/products/create">
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        New Product
                    </Button>
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agencies</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">{{ product.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ Number(product.price || 0).toFixed(2) }} RSD</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ product.unit || '-' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="product.agencies && product.agencies.length > 0">
                                    {{ product.agencies.map(a => a.name).join(', ') }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link :href="`/products/${product.id}/edit`" class="mr-2">
                                    <Button variant="outline" size="sm">
                                        <Edit class="w-4 h-4" />
                                    </Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="deleteProduct(product.id)">
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

