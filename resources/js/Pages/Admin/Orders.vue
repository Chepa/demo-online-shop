<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { api } from '@/lib/api';
import type { Order, OrderItem } from '@/types/order';
import type { PaginationMeta, PaginationLink, PaginationData } from '@/types/pagination';

const orders = ref<Order[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const pagination = ref<PaginationMeta | null>(null);
const paginationLinks = ref<PaginationLink[]>([]);
const currentPage = ref(1);
const expandedOrderId = ref<number | null>(null);
const updatingStatusIds = ref<Set<number>>(new Set());
const successMessage = ref<string | null>(null);

const statusLabels: Record<string, string> = {
    pending: 'В ожидании',
    paid: 'Оплачен',
    shipped: 'Отправлен',
    completed: 'Завершен',
    cancelled: 'Отменен',
};

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusOptions = [
    { value: 'pending', label: 'В ожидании' },
    { value: 'paid', label: 'Оплачен' },
    { value: 'shipped', label: 'Отправлен' },
    { value: 'completed', label: 'Завершен' },
    { value: 'cancelled', label: 'Отменен' },
];

const fetchOrders = async (page: number = 1) => {
    loading.value = true;
    error.value = null;
    currentPage.value = page;

    try {
        const { data } = await api.get<PaginationData>('/admin/orders', {
            params: { page }
        });

        if (data) {
            orders.value = data.data || [];

            pagination.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1,
                per_page: data.per_page || 20,
                total: data.total || 0,
                from: data.from || 0,
                to: data.to || 0,
            };

            paginationLinks.value = data.links || [];
        } else {
            orders.value = [];
            pagination.value = null;
            paginationLinks.value = [];
        }
    } catch (err: any) {
        console.error('Ошибка загрузки заказов:', err);
        error.value = err?.response?.data?.message || 'Не удалось загрузить заказы';
        orders.value = [];
        pagination.value = null;
        paginationLinks.value = [];
    } finally {
        loading.value = false;
    }
};

const goToPage = (page: number) => {
    if (page >= 1 && page <= (pagination.value?.last_page || 1)) {
        fetchOrders(page);
    }
};

const goToNextPage = () => {
    if (pagination.value && pagination.value.current_page < pagination.value.last_page) {
        goToPage(pagination.value.current_page + 1);
    }
};

const goToPrevPage = () => {
    if (pagination.value && pagination.value.current_page > 1) {
        goToPage(pagination.value.current_page - 1);
    }
};

const handlePaginationClick = (link: PaginationLink) => {
    if (!link.url || link.active) return;

    const url = new URL(link.url, window.location.origin);
    const page = url.searchParams.get('page');
    if (page) {
        goToPage(parseInt(page));
    }
};

const toggleOrderDetails = (orderId: number) => {
    expandedOrderId.value = expandedOrderId.value === orderId ? null : orderId;
};

const updateOrderStatus = async (orderId: number, newStatus: string) => {
    updatingStatusIds.value.add(orderId);
    error.value = null;
    successMessage.value = null;

    try {
        const { data } = await api.patch<Order>(`/admin/orders/${orderId}`, {
            status: newStatus
        });

        // Обновляем заказ в списке, сохраняя все данные включая items
        const index = orders.value.findIndex(o => o.id === orderId);
        if (index !== -1) {
            // Сохраняем items из текущего заказа, если они есть, иначе используем из ответа
            const currentOrder = orders.value[index];
            orders.value[index] = {
                ...data,
                // Если в ответе нет items, но они были в текущем заказе, сохраняем их
                items: data.items && data.items.length > 0 ? data.items : (currentOrder.items || []),
            };
        }

        successMessage.value = 'Статус заказа успешно обновлен';
        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    } catch (err: any) {
        console.error('Ошибка обновления статуса заказа:', err);
        const errorMsg = err?.response?.data?.message || 
                        err?.response?.data?.errors?.status?.[0] ||
                        'Не удалось обновить статус заказа';
        error.value = errorMsg;
        setTimeout(() => {
            error.value = null;
        }, 5000);
    } finally {
        updatingStatusIds.value.delete(orderId);
    }
};

onMounted(() => {
    fetchOrders();
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Управление заказами</h1>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div v-if="loading" class="p-8 text-center text-gray-500">
                    Загрузка заказов...
                </div>

                <div v-else-if="orders.length === 0" class="p-8 text-center text-gray-500">
                    Заказы не найдены
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="order in orders"
                        :key="order.id"
                        class="p-6 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Заказ #{{ order.id }}
                                    </h3>
                                    <div class="relative flex items-center space-x-2">
                                        <select
                                            :value="order.status"
                                            @change="updateOrderStatus(order.id, ($event.target as HTMLSelectElement).value)"
                                            :disabled="updatingStatusIds.has(order.id)"
                                            :class="[
                                                statusColors[order.status] || 'bg-gray-100 text-gray-800',
                                                'px-2 py-1 text-xs leading-5 font-semibold rounded-full border-none appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed'
                                            ]"
                                        >
                                            <option
                                                v-for="option in statusOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <span v-if="updatingStatusIds.has(order.id)" class="text-xs text-gray-500">
                                            Обновление...
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium text-gray-900">Клиент:</span>
                                        <p>{{ order.customer_name || order.user?.name || '-' }}</p>
                                        <p v-if="order.user?.email" class="text-xs text-gray-500">
                                            {{ order.user.email }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-900">Телефон:</span>
                                        <p>{{ order.customer_phone || '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-900">Сумма:</span>
                                        <p class="text-gray-900 font-semibold">
                                            {{ Number(order.total).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-900">Дата:</span>
                                        <p>{{ new Date(order.created_at).toLocaleString('ru-RU') }}</p>
                                    </div>
                                </div>

                                <div v-if="order.address_line || order.city || order.postal_code" class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium text-gray-900">Адрес:</span>
                                    <p>
                                        {{ [order.address_line, order.city, order.postal_code].filter(Boolean).join(', ') }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="toggleOrderDetails(order.id)"
                                class="ml-4 text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                {{ expandedOrderId === order.id ? 'Скрыть' : 'Детали' }}
                            </button>
                        </div>

                        <!-- Детали заказа -->
                        <div v-if="expandedOrderId === order.id && order.items && order.items.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Товары в заказе:</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                Товар
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                Количество
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                Цена за единицу
                                            </th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                                Сумма
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in order.items" :key="item.id">
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                {{ item.product?.name || `Товар #${item.product_id}` }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ item.quantity }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600">
                                                {{ Number(item.price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900 text-right font-medium">
                                                {{ (Number(item.price) * item.quantity).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пагинация -->
            <div v-if="pagination && pagination.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg shadow">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button
                        type="button"
                        @click="goToPrevPage"
                        :disabled="pagination.current_page === 1 || loading"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Назад
                    </button>
                    <button
                        type="button"
                        @click="goToNextPage"
                        :disabled="pagination.current_page === pagination.last_page || loading"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Вперед
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Показано
                            <span class="font-medium">{{ pagination.from }}</span>
                            до
                            <span class="font-medium">{{ pagination.to }}</span>
                            из
                            <span class="font-medium">{{ pagination.total }}</span>
                            заказов
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button
                                type="button"
                                @click="goToPrevPage"
                                :disabled="pagination.current_page === 1 || loading"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="sr-only">Предыдущая</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button
                                    v-if="link.label && link.label !== '...' && link.url"
                                    type="button"
                                    @click="handlePaginationClick(link)"
                                    :disabled="link.active || loading"
                                    :class="[
                                        link.active
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed'
                                    ]"
                                >
                                    <span v-html="link.label"></span>
                                </button>
                                <span
                                    v-else-if="link.label === '...' || (link.label && !link.url)"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                >
                                    <span v-html="link.label || '...'"></span>
                                </span>
                            </template>

                            <button
                                type="button"
                                @click="goToNextPage"
                                :disabled="pagination.current_page === pagination.last_page || loading"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="sr-only">Следующая</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
