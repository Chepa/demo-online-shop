<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/lib/api';
import type { Order } from '@/types/order';

const orders = ref<Order[]>([]);
const loading = ref(false);

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await api.get<{ data: Order[] } | Order[]>('/orders');
        orders.value = Array.isArray(data) ? data : data.data || [];
    } catch (e) {
        console.error('Ошибка загрузки заказов:', e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="flex justify-between items-center mb-2">
            <h1 class="text-xl font-semibold text-gray-900">
                Мои заказы
            </h1>
            <RouterLink to="/" class="text-sm text-indigo-600 hover:text-indigo-800">
                Назад в каталог
            </RouterLink>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Загрузка заказов...</p>
        </div>

        <div v-else-if="orders.length === 0" class="text-gray-500">
            Заказов пока нет.
        </div>
        <div v-else class="space-y-4">
            <div
                v-for="order in orders"
                :key="order.id"
                class="bg-white rounded-lg shadow p-4 space-y-1"
            >
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-900">
                        Заказ #{{ order.id }}
                    </p>
                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        {{ order.status }}
                    </p>
                </div>
                <p class="text-sm text-gray-600">
                    Сумма:
                    {{ Number(order.total).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                </p>
                <p class="text-xs text-gray-500">
                    Дата:
                    {{ new Date(order.created_at).toLocaleString('ru-RU') }}
                </p>
            </div>
        </div>
    </div>
</template>
