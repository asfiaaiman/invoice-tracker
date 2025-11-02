<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import ValidationErrors from '@/components/ValidationErrors.vue';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';

interface Props {
    invoice: {
        id: number;
        agency_id: number;
        client_id: number;
        invoice_number: string;
        issue_date: string;
        due_date?: string;
        notes?: string;
        items: Array<{
            id?: number;
            product_id?: number;
            description?: string;
            quantity: number;
            unit_price: number;
        }>;
    };
    agencies: Array<{
        id: number;
        name: string;
    }>;
    clients: Array<{
        id: number;
        name: string;
    }>;
    products: Array<{
        id: number;
        name: string;
        code?: string;
        price: number;
        unit: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/invoices' },
    { title: 'Edit', href: `/invoices/${props.invoice.id}/edit` },
];

const form = useForm({
    agency_id: props.invoice.agency_id.toString(),
    client_id: props.invoice.client_id.toString(),
    invoice_number: props.invoice.invoice_number,
    issue_date: props.invoice.issue_date,
    due_date: props.invoice.due_date || '',
    notes: props.invoice.notes || '',
    items: props.invoice.items.map(item => ({
        product_id: item.product_id?.toString() || '',
        description: item.description || '',
        quantity: item.quantity,
        unit_price: item.unit_price,
        is_custom: !item.product_id,
    })),
});

const clients = ref<Array<{ id: number; name: string }>>(props.clients);
const products = ref<Array<{ id: number; name: string; code?: string; price: number; unit: string }>>(props.products);
const isLoadingClients = ref(false);
const isLoadingProducts = ref(false);

async function loadClients() {
    if (!form.agency_id) {
        clients.value = [];
        return;
    }

    isLoadingClients.value = true;
    try {
        const response = await fetch(`/api/clients?agency_id=${form.agency_id}`);
        const data = await response.json();
        clients.value = data;
    } catch (error) {
        console.error('Failed to load clients:', error);
    } finally {
        isLoadingClients.value = false;
    }
}

async function loadProducts() {
    if (!form.agency_id) {
        products.value = [];
        return;
    }

    isLoadingProducts.value = true;
    try {
        const response = await fetch(`/api/products?agency_id=${form.agency_id}`);
        const data = await response.json();
        products.value = data;
    } catch (error) {
        console.error('Failed to load products:', error);
    } finally {
        isLoadingProducts.value = false;
    }
}

watch(() => form.agency_id, () => {
    if (form.agency_id && form.agency_id !== props.invoice.agency_id.toString()) {
        form.client_id = '';
        loadClients();
        loadProducts();
    }
});

onMounted(() => {
    if (form.agency_id) {
        loadClients();
        loadProducts();
    }
});

function addItem() {
    form.items.push({
        product_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        is_custom: false,
    });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
    if (form.items.length === 0) {
        addItem();
    }
}

function toggleCustomItem(index: number) {
    const item = form.items[index];
    item.is_custom = !item.is_custom;
    if (item.is_custom) {
        item.product_id = '';
    } else {
        item.description = '';
        item.unit_price = 0;
    }
}

function updateItemProduct(index: number, productId: string) {
    const item = form.items[index];
    item.product_id = productId;
    item.is_custom = false;
    
    const product = products.value.find((p) => p.id === parseInt(productId));
    if (product) {
        item.unit_price = product.price;
        item.description = product.name;
    }
}

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity.toString()) || 0) * (parseFloat(item.unit_price.toString()) || 0);
    }, 0);
});

const taxAmount = computed(() => {
    return subtotal.value * 0.20; // 20% VAT
});

const total = computed(() => {
    return subtotal.value + taxAmount.value;
});

function submit() {
    const dataToSubmit = {
        ...form.data(),
        items: form.items.map(item => {
            const hasProduct = item.product_id && item.product_id !== '' && !item.is_custom;
            return {
                product_id: hasProduct ? item.product_id : null,
                description: item.description || null,
                quantity: item.quantity,
                unit_price: item.unit_price,
            };
        }),
    };
    
    form.transform(() => dataToSubmit).put(`/invoices/${props.invoice.id}`, {
        preserveScroll: true,
        onError: () => {
            // Errors will be shown via ValidationErrors component
        },
    });
}
</script>

<template>
    <Head :title="`Edit Invoice - ${invoice.invoice_number}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl">
            <FlashMessage />
            <h1 class="text-2xl font-bold mb-6">Edit Invoice {{ invoice.invoice_number }}</h1>

            <ValidationErrors :errors="form.errors" />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="agency_id">Agency *</Label>
                        <select
                            id="agency_id"
                            v-model="form.agency_id"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                            required
                        >
                            <option value="">Select Agency</option>
                            <option v-for="agency in agencies" :key="agency.id" :value="agency.id">
                                {{ agency.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.agency_id" />
                    </div>

                    <div>
                        <Label for="client_id">Client *</Label>
                        <select
                            id="client_id"
                            v-model="form.client_id"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                            :disabled="!form.agency_id || isLoadingClients"
                            required
                        >
                            <option value="">Select Client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.client_id" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <Label for="invoice_number">Invoice Number *</Label>
                        <Input
                            id="invoice_number"
                            v-model="form.invoice_number"
                            required
                        />
                        <InputError :message="form.errors.invoice_number" />
                    </div>

                    <div>
                        <Label for="issue_date">Issue Date *</Label>
                        <Input id="issue_date" v-model="form.issue_date" type="date" required />
                        <InputError :message="form.errors.issue_date" />
                    </div>

                    <div>
                        <Label for="due_date">Due Date</Label>
                        <Input id="due_date" v-model="form.due_date" type="date" />
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>

                <div>
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                        rows="3"
                    ></textarea>
                    <InputError :message="form.errors.notes" />
                </div>

                <div>
                    <div class="flex justify-between items-center mb-4">
                        <Label>Items *</Label>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="w-4 h-4 mr-2" />
                            Add Item
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 border rounded-lg dark:border-gray-700"
                        >
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="font-medium">Item {{ index + 1 }}</h4>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <input
                                        type="checkbox"
                                        :id="`custom_${index}`"
                                        :checked="item.is_custom"
                                        @change="toggleCustomItem(index)"
                                        class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <Label :for="`custom_${index}`" class="font-normal cursor-pointer text-sm">
                                        Custom item (no product)
                                    </Label>
                                </div>

                                <div v-if="!item.is_custom" class="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label :for="`product_${index}`">Product</Label>
                                        <select
                                            :id="`product_${index}`"
                                            :value="item.product_id"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                            :disabled="!form.agency_id || isLoadingProducts"
                                            @change="updateItemProduct(index, ($event.target as HTMLSelectElement).value)"
                                        >
                                            <option value="">Select Product</option>
                                            <option
                                                v-for="product in products"
                                                :key="product.id"
                                                :value="product.id"
                                            >
                                                {{ product.name }} 
                                                <template v-if="product.code">({{ product.code }})</template>
                                                - {{ product.price.toFixed(2) }} RSD / {{ product.unit }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors[`items.${index}.product_id`]" />
                                    </div>

                                    <div>
                                        <Label :for="`description_${index}`">Description</Label>
                                        <Input
                                            :id="`description_${index}`"
                                            v-model="item.description"
                                            placeholder="Auto-filled from product"
                                            :disabled="!!item.product_id"
                                        />
                                        <InputError :message="form.errors[`items.${index}.description`]" />
                                    </div>
                                </div>

                                <div v-else>
                                    <Label :for="`description_${index}`">Description *</Label>
                                    <Input
                                        :id="`description_${index}`"
                                        v-model="item.description"
                                        placeholder="Enter item description"
                                        required
                                    />
                                    <InputError :message="form.errors[`items.${index}.description`]" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <Label :for="`quantity_${index}`">Quantity *</Label>
                                    <Input
                                        :id="`quantity_${index}`"
                                        v-model.number="item.quantity"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                    />
                                    <InputError :message="form.errors[`items.${index}.quantity`]" />
                                </div>

                                <div>
                                    <Label :for="`unit_price_${index}`">Unit Price *</Label>
                                    <Input
                                        :id="`unit_price_${index}`"
                                        v-model.number="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                    />
                                    <InputError :message="form.errors[`items.${index}.unit_price`]" />
                                </div>

                                <div>
                                    <Label>Total</Label>
                                    <div class="px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                                        {{ ((item.quantity || 0) * (item.unit_price || 0)).toFixed(2) }} RSD
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <InputError v-if="form.errors.items" :message="form.errors.items" />
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-end space-x-4">
                        <div class="text-right space-y-1">
                            <div class="text-sm">
                                <span class="font-medium">Subtotal:</span>
                                <span class="ml-2">{{ subtotal.toFixed(2) }} RSD</span>
                            </div>
                            <div class="text-sm">
                                <span class="font-medium">VAT (20%):</span>
                                <span class="ml-2">{{ taxAmount.toFixed(2) }} RSD</span>
                            </div>
                            <div class="text-lg font-bold pt-2 border-t">
                                <span>Total:</span>
                                <span class="ml-2">{{ total.toFixed(2) }} RSD</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="form.processing">Update Invoice</Button>
                    <Button type="button" variant="outline" @click="$inertia.visit(`/invoices/${invoice.id}`)">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

