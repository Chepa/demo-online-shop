<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const logout = async () => {
    await auth.logout();
    window.location.href = '/';
};
</script>

<template>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <RouterLink to="/admin" class="text-xl font-semibold text-gray-900">
                    Админ-панель
                </RouterLink>
                <RouterLink to="/admin" class="text-sm text-gray-700 hover:text-gray-900">
                    Главная
                </RouterLink>
                <RouterLink to="/admin/products" class="text-sm text-gray-700 hover:text-gray-900">
                    Товары
                </RouterLink>
                <RouterLink to="/admin/categories" class="text-sm text-gray-700 hover:text-gray-900">
                    Категории
                </RouterLink>
                <RouterLink to="/admin/orders" class="text-sm text-gray-700 hover:text-gray-900">
                    Заказы
                </RouterLink>
            </div>
            <div class="flex items-center space-x-4">
                <RouterLink to="/" class="text-sm text-indigo-600 hover:text-indigo-800">
                    В магазин
                </RouterLink>
                <button
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
