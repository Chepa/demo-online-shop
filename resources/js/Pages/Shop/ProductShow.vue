<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { useProductStore } from '@/stores/product';

const route = useRoute();
const router = useRouter();
const productId = Number(route.params.id);

const cart = useCartStore();
const auth = useAuthStore();
const productStore = useProductStore();

const quantity = ref(1);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
const loadError = ref<string | null>(null);

const product = computed(() => productStore.currentProduct);

onMounted(async () => {
    try {
        loadError.value = null;
        await productStore.fetchProduct(productId);
    } catch (error: any) {
        console.error('Ошибка загрузки товара:', error);
        if (error.response?.status === 404) {
            loadError.value = 'Товар не найден';
        } else {
            loadError.value = 'Произошла ошибка при загрузке товара. Попробуйте обновить страницу.';
        }
    }
});

const handleImageError = (event: Event) => {
    const target = event.target as HTMLImageElement;
    if (target) {
        target.style.display = 'none';
    }
};

const addToCart = async () => {
    if (!auth.isAuthenticated) {
        await router.push('/login');
        return;
    }

    successMessage.value = null;
    errorMessage.value = null;

    const result = await cart.add(product.value!.id, quantity.value);

    if (result.success) {
        successMessage.value = 'Товар добавлен в корзину!';
        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    } else {
        errorMessage.value = result.message;
        setTimeout(() => {
            errorMessage.value = null;
        }, 5000);
    }
};
</script>

<template>
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Детали товара</h1>
            <RouterLink to="/" class="text-sm text-indigo-600 hover:text-indigo-800">
                &larr; Назад в каталог
            </RouterLink>
        </div>

        <div v-if="productStore.loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Загрузка товара...</p>
        </div>

        <div v-else-if="!product || loadError" class="text-center py-12 bg-white rounded-lg shadow">
            <div class="max-w-md mx-auto">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 mb-2">
                    {{ loadError || 'Товар не найден' }}
                </h3>
                <p class="text-sm text-gray-500 mb-6">
                    {{ loadError ? 'Попробуйте обновить страницу или вернуться в каталог' : 'Возможно, он был удален или перемещен.' }}
                </p>
                <RouterLink
                    to="/"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Вернуться в каталог
                </RouterLink>
            </div>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden md:flex">
            <div class="md:w-1/2 p-6 flex items-center justify-center bg-gray-50">
                <img
                    v-if="product.image"
                    :src="product.image"
                    :alt="product.name"
                    class="w-full h-96 object-cover rounded-lg shadow-md"
                    @error="handleImageError"
                />
                <div v-else class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-sm">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-2">Нет изображения</p>
                    </div>
                </div>
            </div>

            <div class="md:w-1/2 p-6 space-y-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-3">
                        {{ product.name }}
                    </h2>
                    <div class="flex items-center space-x-4 mb-4">
                        <p v-if="product.category" class="text-sm text-gray-500">
                            Категория:
                            <RouterLink :to="`/?category_id=${product.category.id}`" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                {{ product.category.name }}
                            </RouterLink>
                        </p>
                        <span v-if="product.slug" class="text-xs text-gray-400">ID: {{ product.id }}</span>
                    </div>
                </div>

                <div class="border-t border-b border-gray-200 py-4">
                    <p class="text-4xl font-extrabold text-indigo-600">
                        {{ Number(product.price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                    </p>
                </div>

                <div v-if="product.description" class="border-t border-gray-200 pt-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Описание</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                        {{ product.description }}
                    </p>
                </div>

                <div class="border-t border-gray-200 pt-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <label for="quantity" class="text-gray-700 font-medium">Количество:</label>
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button
                                type="button"
                                @click="quantity = Math.max(1, quantity - 1)"
                                :disabled="quantity <= 1"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                -
                            </button>
                            <input
                                type="number"
                                id="quantity"
                                v-model.number="quantity"
                                min="1"
                                :max="product.stock"
                                class="w-20 text-center border-x border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 py-2"
                                @change="quantity = Math.max(1, Math.min(product.stock, quantity))"
                            >
                            <button
                                type="button"
                                @click="quantity = Math.min(product.stock, quantity + 1)"
                                :disabled="quantity >= product.stock"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-sm text-gray-600">Наличие:</span>
                        <div class="flex items-center space-x-2">
                            <span v-if="product.stock > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                В наличии: <span class="font-semibold ml-1">{{ product.stock }}</span> шт.
                            </span>
                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Нет в наличии
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="successMessage" class="p-4 bg-green-50 border-l-4 border-green-400 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="errorMessage" class="p-4 bg-red-50 border-l-4 border-red-400 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-full inline-flex justify-center items-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    :disabled="!auth.isAuthenticated || cart.isAdding(product.id) || product.stock === 0"
                    @click="addToCart"
                >
                    <svg v-if="!cart.isAdding(product.id) && auth.isAuthenticated && product.stock > 0" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span v-if="cart.isAdding(product.id)">Добавление...</span>
                    <span v-else-if="!auth.isAuthenticated">Войдите, чтобы добавить в корзину</span>
                    <span v-else-if="product.stock === 0">Нет в наличии</span>
                    <span v-else>Добавить в корзину</span>
                </button>
            </div>
        </div>
    </div>
</template>
