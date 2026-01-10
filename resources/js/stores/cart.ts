import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@/lib/api';
import type { CartProduct, CartItem } from '@/types/cart';

export type { CartProduct, CartItem };

export const useCartStore = defineStore('cart', () => {
    const items = ref<CartItem[]>([]);
    const loading = ref(false);
    const adding = ref<Record<number, boolean>>({});

    const count = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0));
    const total = computed(() =>
        items.value.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0)
    );

    async function fetch() {
        loading.value = true;
        try {
            const { data } = await api.get<CartItem[]>('/cart');
            items.value = data;
        } catch (error) {
            items.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function add(productId: number, quantity = 1): Promise<{ success: true } | { success: false; message: string }> {
        adding.value[productId] = true;
        try {
            const { data } = await api.post<CartItem>('/cart', { product_id: productId, quantity });
            const index = items.value.findIndex((i) => i.product_id === data.product_id);
            if (index !== -1) {
                items.value[index] = data;
            } else {
                items.value.push(data);
            }
            return { success: true };
        } catch (error: any) {
            const message = error?.response?.data?.message ||
                error?.response?.data?.errors?.product_id?.[0] ||
                'Не удалось добавить товар в корзину';
            return { success: false, message };
        } finally {
            adding.value[productId] = false;
        }
    }

    function isAdding(productId: number): boolean {
        return !!adding.value[productId];
    }

    async function update(productId: number, quantity: number) {
        try {
            const { data } = await api.put<CartItem>(`/cart/${productId}`, { quantity });
            const index = items.value.findIndex((i) => i.product_id === productId);
            if (index !== -1) {
                items.value[index] = data;
            }
        } catch (error) {
            throw error;
        }
    }

    async function remove(productId: number) {
        try {
            await api.delete(`/cart/${productId}`);
            items.value = items.value.filter((i) => i.product_id !== productId);
        } catch (error) {
            throw error;
        }
    }

    function clear() {
        items.value = [];
    }

    return {
        items,
        loading,
        count,
        total,
        fetch,
        add,
        update,
        remove,
        clear,
        isAdding,
    };
});


