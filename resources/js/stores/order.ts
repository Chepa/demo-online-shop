import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '@/lib/api';
import type { OrderItem, Order } from '@/types/order';
export type { OrderItem, Order };

export const useOrderStore = defineStore('order', () => {
    const orders = ref<Order[]>([]);
    const currentOrder = ref<Order | null>(null);
    const loading = ref(false);

    async function fetchOrders() {
        loading.value = true;
        try {
            const { data } = await api.get<{ data: Order[] } | Order[]>('/orders');
            orders.value = Array.isArray(data) ? data : data.data || [];
        } catch (error) {
            console.error('Ошибка загрузки заказов:', error);
            orders.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrder(id: number) {
        loading.value = true;
        try {
            const { data } = await api.get<Order>(`/orders/${id}`);
            currentOrder.value = data;
            return data;
        } catch (error) {
            console.error('Ошибка загрузки заказа:', error);
            currentOrder.value = null;
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function createOrder(orderData: {
        customer_name: string;
        customer_email?: string;
        customer_phone?: string;
        customer_address?: string;
    }) {
        loading.value = true;
        try {
            const { data } = await api.post<Order>('/orders', orderData);
            orders.value.unshift(data);
            return data;
        } catch (error) {
            console.error('Ошибка создания заказа:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    return {
        orders,
        currentOrder,
        loading,
        fetchOrders,
        fetchOrder,
        createOrder,
    };
});

