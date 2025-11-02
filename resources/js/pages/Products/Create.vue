<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';

interface Props {
    agencies: Array<{
        id: number;
        name: string;
    }>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
    { title: 'Create', href: '/products/create' },
];

const form = useForm({
    name: '',
    description: '',
    price: 0,
    unit: 'hour',
    code: '',
    agency_ids: [] as number[],
});

function submit() {
    // Ensure agency_ids is sent as an array even if empty
    if (!Array.isArray(form.agency_ids)) {
        form.agency_ids = [];
    }
    form.post('/products');
}

const agencyChecked = (agencyId: number) => {
    const ids = form.agency_ids || [];
    return ids.includes(agencyId);
};

const toggleAgency = (agencyId: number, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const currentIds = form.agency_ids || [];
    if (currentIds.includes(agencyId)) {
        form.agency_ids = currentIds.filter((id: number) => id !== agencyId);
    } else {
        form.agency_ids = [...currentIds, agencyId];
    }
    // Force form to recognize the change
    form.agency_ids = [...form.agency_ids];
};
</script>

<template>
    <Head title="Create Product" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Create Product</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="w-full px-3 py-2 border rounded-md"
                        rows="3"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="price">Price (RSD) *</Label>
                        <Input id="price" type="number" step="0.01" v-model="form.price" />
                        <InputError :message="form.errors.price" />
                    </div>
                    <div>
                        <Label for="unit">Unit</Label>
                        <Input id="unit" v-model="form.unit" placeholder="hour, piece, day, etc." />
                        <InputError :message="form.errors.unit" />
                    </div>
                </div>

                <div>
                    <Label for="code">Code</Label>
                    <Input id="code" v-model="form.code" />
                    <InputError :message="form.errors.code" />
                </div>

                <div>
                    <Label>Agencies *</Label>
                    <div class="space-y-2 mt-2">
                        <div
                            v-for="agency in agencies"
                            :key="agency.id"
                            class="flex items-center space-x-2"
                        >
                            <input
                                type="checkbox"
                                :id="`agency_${agency.id}`"
                                :checked="agencyChecked(agency.id)"
                                @change="toggleAgency(agency.id, $event)"
                                class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label :for="`agency_${agency.id}`" class="font-normal cursor-pointer">
                                {{ agency.name }}
                            </Label>
                        </div>
                    </div>
                    <InputError :message="form.errors.agency_ids" />
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">Create</Button>
                    <Button type="button" variant="outline" @click="$inertia.visit('/products')">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

