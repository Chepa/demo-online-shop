<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

const router = useRouter();
const auth = useAuthStore();
const cart = useCartStore();

const logout = async () => {
    await auth.logout();
    cart.clear();
    window.location.href = '/';
};
</script>

<template>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <RouterLink to="/" class="text-xl font-semibold text-gray-900">
                    Online Shop
                </RouterLink>
                <RouterLink to="/" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Каталог
                </RouterLink>
                <RouterLink
                    v-if="auth.isAdmin"
                    to="/admin"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Админ
                </RouterLink>
            </div>

            <div class="flex items-center space-x-4">
                <RouterLink
                    v-if="!auth.isAuthenticated"
                    to="/login"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Вход
                </RouterLink>
                <span v-else class="text-sm text-gray-700">{{ auth.user?.name }}</span>
                <RouterLink
                    v-if="!auth.isAuthenticated"
                    to="/register"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Регистрация
                </RouterLink>
                <RouterLink
                    v-if="auth.isAuthenticated"
                    to="/orders"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Мои заказы
                </RouterLink>
                <RouterLink
                    v-if="auth.isAuthenticated"
                    to="/cart"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Корзина ({{ cart.count }})
                </RouterLink>
                <button
                    v-if="auth.isAuthenticated"
                    type="button"
                    @click="logout"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Выход
                </button>
            </div>
        </div>
    </header>
</template>
