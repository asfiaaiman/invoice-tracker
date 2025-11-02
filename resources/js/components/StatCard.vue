<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';

interface Props {
    title: string;
    value: string | number;
    icon?: Component;
    href?: string;
    description?: string;
    trend?: {
        value: number;
        label: string;
        isPositive?: boolean;
    };
}

const props = defineProps<Props>();

const displayValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString('sr-RS');
    }
    return props.value;
});
</script>

<template>
    <Card :class="{ 'cursor-pointer hover:shadow-md transition-shadow': href }">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
            <component v-if="icon" :is="icon" class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
            <div v-if="href" class="flex items-baseline gap-2">
                <Link :href="href" class="text-2xl font-bold hover:underline">{{ displayValue }}</Link>
            </div>
            <div v-else class="text-2xl font-bold">{{ displayValue }}</div>
            <p v-if="description" class="text-xs text-muted-foreground mt-1">{{ description }}</p>
            <div v-if="trend" class="flex items-center gap-1 text-xs mt-1">
                <span
                    :class="{
                        'text-green-600 dark:text-green-400': trend.isPositive !== false,
                        'text-red-600 dark:text-red-400': trend.isPositive === false,
                    }"
                >
                    {{ trend.isPositive !== false ? '↑' : '↓' }}
                    {{ Math.abs(trend.value) }}%
                </span>
                <span class="text-muted-foreground">{{ trend.label }}</span>
            </div>
        </CardContent>
    </Card>
</template>
