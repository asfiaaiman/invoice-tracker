<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FlashMessage from '@/components/FlashMessage.vue';
import { BarChart3 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    agencies: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
];

const selectOpen = ref(false);

function viewReport(agencyId: number) {
    window.location.href = `/reports/${agencyId}`;
}
</script>

<template>
    <Head title="Reports" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Agency Reports</h1>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Select Agency</label>
                        <div class="flex gap-4">
                            <Select
                                v-model:open="selectOpen"
                                @update:model-value="(val) => val && viewReport(parseInt(val))"
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Choose an agency..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="agency in agencies"
                                        :key="agency.id"
                                        :value="agency.id.toString()"
                                    >
                                        {{ agency.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div v-if="agencies.length === 0" class="text-gray-500 text-center py-8">
                        No agencies available. Please create an agency first.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <Link
                            v-for="agency in agencies"
                            :key="agency.id"
                            :href="`/reports/${agency.id}`"
                            class="p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            <div class="flex items-center gap-3">
                                <BarChart3 class="w-8 h-8 text-blue-500" />
                                <div>
                                    <h3 class="font-semibold">{{ agency.name }}</h3>
                                    <p class="text-sm text-gray-500">View report</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <Link href="/reports/period">
                    <Button variant="outline">
                        Report by Period
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

