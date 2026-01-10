<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/lib/api';

const cart = useCartStore();
const auth = useAuthStore();
const router = useRouter();

onMounted(async () => {
    if (auth.isAuthenticated) {
        await cart.fetch();
    }
});

const checkout = async () => {
    if (!auth.user) return;

    try {
        await api.post('/orders', {
            customer_name: auth.user.name,
            customer_email: auth.user.email,
        });
        cart.clear();
        router.push('/orders');
    } catch (error) {
        console.error('Ошибка оформления заказа:', error);
    }
};
</script>

<template>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex justify-between items-center mb-2">
            <h1 class="text-xl font-semibold text-gray-900">
                Корзина
            </h1>
            <RouterLink to="/" class="text-sm text-indigo-600 hover:text-indigo-800">
                Назад в каталог
            </RouterLink>
        </div>

        <div v-if="cart.items.length === 0" class="text-gray-500">
            Корзина пуста.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="item in cart.items"
                :key="item.id"
                class="flex items-center justify-between bg-white rounded-lg shadow p-4"
            >
                <div>
                    <h2 class="text-sm font-medium text-gray-900">
                        {{ item.product.name }}
                    </h2>
                    <p class="text-xs text-gray-500">
                        {{ Number(item.product.price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">
                        x {{ item.quantity }}
                    </span>
                    <button
                        type="button"
                        class="text-xs text-red-600 hover:text-red-800"
                        @click="cart.remove(item.product_id)"
                    >
                        Удалить
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <p class="text-sm font-semibold text-gray-900">
                    Итого:
                    {{ cart.total.toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                </p>
                <button
                    type="button"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    @click="checkout"
                >
                    Оформить заказ
                </button>
            </div>
        </div>
    </div>
</template>

