<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';

interface Props {
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
        note?: string;
        agencies?: Array<{ id: number; name: string }>;
    };
    agencies: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Edit', href: `/clients/${props.client.id}/edit` },
];

const form = useForm({
    name: props.client.name,
    tax_id: props.client.tax_id || '',
    address: props.client.address || '',
    city: props.client.city || '',
    zip_code: props.client.zip_code || '',
    country: props.client.country || 'Serbia',
    email: props.client.email || '',
    phone: props.client.phone || '',
    note: props.client.note || '',
    agency_ids: props.client.agencies?.map(a => a.id) || [],
});

function submit() {
    form.put(`/clients/${props.client.id}`, {
        preserveScroll: true,
        onSuccess: () => {},
        onError: () => {},
    });
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
    <Head title="Edit Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Edit Client</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <Label for="tax_id">Tax ID</Label>
                    <Input id="tax_id" v-model="form.tax_id" />
                    <InputError :message="form.errors.tax_id" />
                </div>

                <div>
                    <Label for="address">Address</Label>
                    <Input id="address" v-model="form.address" />
                    <InputError :message="form.errors.address" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" />
                        <InputError :message="form.errors.city" />
                    </div>
                    <div>
                        <Label for="zip_code">Zip Code</Label>
                        <Input id="zip_code" v-model="form.zip_code" />
                        <InputError :message="form.errors.zip_code" />
                    </div>
                </div>

                <div>
                    <Label for="country">Country</Label>
                    <Input id="country" v-model="form.country" />
                    <InputError :message="form.errors.country" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="form.email" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div>
                    <Label for="note">Note</Label>
                    <textarea
                        id="note"
                        v-model="form.note"
                        class="w-full px-3 py-2 border rounded-md"
                        rows="3"
                    />
                    <InputError :message="form.errors.note" />
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
                    <Button type="submit" :disabled="form.processing">Update</Button>
                    <Button type="button" variant="outline" @click="$inertia.visit('/clients')">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

