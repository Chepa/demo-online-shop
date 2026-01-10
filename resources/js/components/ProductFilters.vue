<script setup lang="ts">
import { computed } from 'vue';
import type { Category } from '@/types/product';

interface Props {
    categories: Category[];
    categoryId: number | null;
    search: string;
    minPrice: number | null;
    maxPrice: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:categoryId': [value: number | null];
    'update:search': [value: string];
    'update:minPrice': [value: number | null];
    'update:maxPrice': [value: number | null];
    'clear': [];
}>();

const hasActiveFilters = computed(() => {
    return !!props.categoryId || !!props.search || !!props.minPrice || !!props.maxPrice;
});

const clearFilters = () => {
    emit('clear');
};
</script>

<template>
    <div class="bg-white rounded-lg shadow p-4 space-y-4 sticky top-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Фильтры</h3>
            <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                Сбросить
            </button>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
            <select
                :value="categoryId"
                @change="emit('update:categoryId', $event.target.value ? Number(($event.target as HTMLSelectElement).value) : null)"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option :value="null">Все категории</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                >
                    {{ category.name }}
                </option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
            <input
                :value="search"
                @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                type="text"
                placeholder="Название товара..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
            <div class="space-y-2">
                <input
                    :value="minPrice"
                    @input="emit('update:minPrice', ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
                    type="number"
                    placeholder="От"
                    min="0"
                    step="0.01"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                <input
                    :value="maxPrice"
                    @input="emit('update:maxPrice', ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
                    type="number"
                    placeholder="До"
                    min="0"
                    step="0.01"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
            </div>
        </div>
    </div>
</template>
