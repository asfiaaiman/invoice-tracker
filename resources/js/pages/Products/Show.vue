<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-vue-next';

interface Props {
    product: {
        id: number;
        name: string;
        description?: string;
        price: number;
        unit?: string;
        code?: string;
        agencies?: Array<{ id: number; name: string }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
    { title: props.product.name, href: `/products/${props.product.id}` },
];
</script>

<template>
    <Head :title="`Product - ${product.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">{{ product.name }}</h1>
                <Link :href="`/products/${product.id}/edit`">
                    <Button variant="outline">
                        <Edit class="w-4 h-4 mr-2" />
                        Edit
                    </Button>
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Product Information</h2>
                <div class="space-y-2">
                    <div>
                        <span class="text-gray-500">Description:</span>
                        <p class="ml-2">{{ product.description || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Price:</span>
                        <span class="ml-2 font-semibold">{{ product.price.toFixed(2) }} RSD</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Unit:</span>
                        <span class="ml-2">{{ product.unit || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Code:</span>
                        <span class="ml-2">{{ product.code || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Agencies:</span>
                        <div class="ml-2 mt-1">
                            <div v-if="product.agencies && product.agencies.length > 0">
                                <Link
                                    v-for="agency in product.agencies"
                                    :key="agency.id"
                                    :href="`/agencies/${agency.id}`"
                                    class="text-blue-600 hover:underline block"
                                >
                                    {{ agency.name }}
                                </Link>
                            </div>
                            <span v-else class="text-gray-400">No agencies assigned</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

