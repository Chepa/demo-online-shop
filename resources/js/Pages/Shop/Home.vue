<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { useProductStore } from '@/stores/product';
import ProductFilters from '@/components/ProductFilters.vue';
import type { PaginationLink } from '@/types/pagination';

const route = useRoute();
const router = useRouter();

const categoryId = ref<number | null>(null);
const search = ref('');
const minPrice = ref<number | null>(null);
const maxPrice = ref<number | null>(null);
const successProductId = ref<number | null>(null);

const cart = useCartStore();
const auth = useAuthStore();
const productStore = useProductStore();

const products = computed(() => productStore.products);
const loading = computed(() => productStore.loading);
const pagination = computed(() => productStore.pagination);
const paginationLinks = computed(() => productStore.paginationLinks);
const currentPage = computed({
    get: () => productStore.currentPage,
    set: (val: number) => {
        productStore.currentPage = val;
    },
});

const readFiltersFromUrl = () => {
    const query = route.query;

    if (query.category_id) {
        const catId = Number(query.category_id);
        categoryId.value = Number.isFinite(catId) ? catId : null;
    } else {
        categoryId.value = null;
    }

    search.value = (query.search as string) || '';

    if (query.min_price) {
        const min = Number(query.min_price);
        minPrice.value = Number.isFinite(min) && min > 0 ? min : null;
    } else {
        minPrice.value = null;
    }

    if (query.max_price) {
        const max = Number(query.max_price);
        maxPrice.value = Number.isFinite(max) && max > 0 ? max : null;
    } else {
        maxPrice.value = null;
    }

    const page = Number(query.page || 1);
    currentPage.value = Number.isNaN(page) || page < 1 ? 1 : page;
};

const updateUrl = () => {
    const query: Record<string, string | number> = {};

    if (categoryId.value) query.category_id = categoryId.value;
    if (search.value) query.search = search.value;
    if (minPrice.value) query.min_price = minPrice.value;
    if (maxPrice.value) query.max_price = maxPrice.value;
    if (currentPage.value && currentPage.value > 1) query.page = currentPage.value;

    router.replace({ query });
};

const applyFilters = async () => {
    const filters: Record<string, any> = {};

    if (categoryId.value) filters.category_id = categoryId.value;
    if (search.value) filters.search = search.value;
    if (minPrice.value) filters.min_price = minPrice.value;
    if (maxPrice.value) filters.max_price = maxPrice.value;

    await productStore.fetchProducts({
        ...filters,
        page: currentPage.value,
    });
};

onMounted(async () => {
    await productStore.fetchCategories();
    readFiltersFromUrl();
    await applyFilters();
});

watch(
    () => route.query,
    () => {
        readFiltersFromUrl();
        applyFilters();
    },
    { deep: true }
);

const addToCart = async (productId: number) => {
    if (!auth.isAuthenticated) {
        await router.push('/login');
        return;
    }

    successProductId.value = null;
    const result = await cart.add(productId, 1);

    if (result.success) {
        successProductId.value = productId;
        setTimeout(() => {
            successProductId.value = null;
        }, 2000);
    }
};
const clearFilters = () => {
    categoryId.value = null;
    search.value = '';
    minPrice.value = null;
    maxPrice.value = null;
    currentPage.value = 1;
    updateUrl();
};

const hasActiveFilters = computed(() => {
    return !!categoryId.value || !!search.value || !!minPrice.value || !!maxPrice.value;
});

let filterChangeTimeout: ReturnType<typeof setTimeout> | null = null;

const handleFilterChange = () => {
    currentPage.value = 1;

    if (filterChangeTimeout) {
        clearTimeout(filterChangeTimeout);
    }

    const delay = search.value ? 300 : 0;

    filterChangeTimeout = setTimeout(() => {
        updateUrl();
    }, delay);
};

const goToPage = (page: number) => {
    if (!pagination.value) return;
    if (page < 1 || page > pagination.value.last_page) return;
    currentPage.value = page;
    updateUrl();
};

const goToNextPage = () => {
    if (!pagination.value) return;
    if (pagination.value.current_page < pagination.value.last_page) {
        goToPage(pagination.value.current_page + 1);
    }
};

const goToPrevPage = () => {
    if (!pagination.value) return;
    if (pagination.value.current_page > 1) {
        goToPage(pagination.value.current_page - 1);
    }
};

const handlePaginationClick = (link: PaginationLink) => {
    if (!link.url || link.active) return;
    const url = new URL(link.url, window.location.origin);
    const page = url.searchParams.get('page');
    if (!page) return;
    goToPage(parseInt(page, 10));
};
</script>

<template>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:space-x-8">
            <aside class="w-full md:w-64 mb-6 md:mb-0">
                <ProductFilters
                    :categories="productStore.categories"
                    :category-id="categoryId"
                    :search="search"
                    :min-price="minPrice"
                    :max-price="maxPrice"
                    @update:category-id="(val) => { categoryId = val; handleFilterChange(); }"
                    @update:search="(val) => { search = val; handleFilterChange(); }"
                    @update:min-price="(val) => { minPrice = val; handleFilterChange(); }"
                    @update:max-price="(val) => { maxPrice = val; handleFilterChange(); }"
                    @clear="clearFilters"
                />
            </aside>

            <section class="flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        Каталог
                        <span v-if="loading" class="text-sm font-normal text-gray-500 ml-2">(загрузка...)</span>
                    </h2>
                    <div v-if="hasActiveFilters" class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>Активные фильтры:</span>
                        <span v-if="categoryId" class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded">
                            Категория: {{ productStore.categories.find(c => c.id === categoryId)?.name }}
                        </span>
                        <span v-if="search" class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded">
                            Поиск: "{{ search }}"
                        </span>
                        <span v-if="minPrice || maxPrice" class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded">
                            Цена: {{ minPrice ? `${minPrice} ₽` : '0' }} - {{ maxPrice ? `${maxPrice} ₽` : '∞' }}
                        </span>
                    </div>
                </div>

                <div v-if="loading && products.length === 0" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p class="mt-4 text-gray-600">Загрузка товаров...</p>
                </div>

                <div
                    v-else-if="products.length === 0"
                    class="text-center py-12 bg-white rounded-lg shadow"
                >
                    <p class="text-gray-500 text-lg mb-2">Товары не найдены</p>
                    <p class="text-sm text-gray-400">
                        <span v-if="hasActiveFilters">Попробуйте изменить параметры фильтрации</span>
                        <span v-else>В каталоге пока нет товаров</span>
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <article
                        v-for="product in products"
                        :key="product.id"
                        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow flex flex-col"
                    >
                        <div class="p-4 flex-1 flex flex-col">
                            <h3 class="text-lg font-semibold mb-2 text-gray-900">
                                {{ product.name }}
                            </h3>
                            <p v-if="product.category" class="text-xs text-gray-500 mb-2">
                                {{ product.category.name }}
                            </p>
                            <p class="text-xl font-bold text-indigo-600 mb-4">
                                {{ Number(product.price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                            </p>
                            <p v-if="product.description" class="text-sm text-gray-600 mb-4 line-clamp-2 flex-1">
                                {{ product.description }}
                            </p>
                            <div class="mt-auto flex items-center justify-between space-x-2 pt-4 border-t border-gray-100">
                                <RouterLink
                                    :to="`/products/${product.id}`"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                >
                                    Подробнее
                                </RouterLink>
                                <button
                                    type="button"
                                    class="inline-flex justify-center py-1.5 px-3 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    :disabled="!auth.isAuthenticated || cart.isAdding(product.id) || product.stock === 0"
                                    :class="successProductId === product.id ? 'bg-green-600 hover:bg-green-700' : ''"
                                    @click="addToCart(product.id)"
                                >
                                    <span v-if="cart.isAdding(product.id)">...</span>
                                    <span v-else-if="successProductId === product.id">✓ Добавлено</span>
                                    <span v-else-if="product.stock === 0">Нет в наличии</span>
                                    <span v-else>В корзину</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Пагинация -->
                <div
                    v-if="pagination && pagination.last_page > 1"
                    class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
                >
                    <!-- Мобильная версия (до 640px) -->
                    <div class="flex items-center justify-between px-3 py-3 sm:hidden">
                        <button
                            type="button"
                            @click="goToPrevPage"
                            :disabled="pagination.current_page === 1 || loading"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-gray-700 disabled:hover:border-gray-300"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span class="hidden xs:inline">Назад</span>
                        </button>
                        <div class="text-xs text-gray-600 px-2">
                            <span class="font-semibold text-gray-900">{{ pagination.current_page }}</span>
                            <span class="mx-1">/</span>
                            <span class="font-semibold text-gray-900">{{ pagination.last_page }}</span>
                        </div>
                        <button
                            type="button"
                            @click="goToNextPage"
                            :disabled="pagination.current_page === pagination.last_page || loading"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-gray-700 disabled:hover:border-gray-300"
                        >
                            <span class="hidden xs:inline">Вперед</span>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Планшетная версия (640px - 1024px) -->
                    <div class="hidden sm:flex md:hidden items-center justify-between px-4 py-3">
                        <div class="flex items-center space-x-1 text-xs">
                            <span class="text-gray-600">Стр.</span>
                            <span class="px-2 py-1 font-semibold text-indigo-600 bg-indigo-50 rounded">{{ pagination.current_page }}</span>
                            <span class="text-gray-600">из</span>
                            <span class="px-2 py-1 font-semibold text-gray-900 bg-gray-100 rounded">{{ pagination.last_page }}</span>
                        </div>

                        <nav class="flex items-center space-x-0.5 overflow-x-auto" aria-label="Pagination">
                            <button
                                type="button"
                                @click="goToPrevPage"
                                :disabled="pagination.current_page === 1 || loading"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button
                                    v-if="link.label && link.label !== '...' && link.url && (link.active || Math.abs(parseInt(link.label) - pagination.current_page) <= 1 || parseInt(link.label) === 1 || parseInt(link.label) === pagination.last_page)"
                                    type="button"
                                    @click="handlePaginationClick(link)"
                                    :disabled="link.active || loading"
                                    :class="[
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-md'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300',
                                        'inline-flex items-center justify-center min-w-[2rem] h-9 px-2 rounded-lg border text-xs font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0'
                                    ]"
                                >
                                    <span v-html="link.label"></span>
                                </button>
                                <span
                                    v-else-if="link.label && link.label === '...'"
                                    class="inline-flex items-center justify-center min-w-[2rem] h-9 px-2 text-xs font-medium text-gray-500 flex-shrink-0"
                                >
                                    <span v-html="link.label"></span>
                                </span>
                            </template>

                            <button
                                type="button"
                                @click="goToNextPage"
                                :disabled="pagination.current_page === pagination.last_page || loading"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </nav>
                    </div>

                    <!-- Десктопная версия (1024px+) -->
                    <div class="hidden md:flex items-center justify-between px-6 py-4">
                        <div class="flex items-center space-x-2 flex-wrap">
                            <p class="text-sm text-gray-600">
                                Показано
                            </p>
                            <span class="px-2 py-1 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-md">
                                {{ pagination.from }}
                            </span>
                            <p class="text-sm text-gray-600">—</p>
                            <span class="px-2 py-1 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-md">
                                {{ pagination.to }}
                            </span>
                            <p class="text-sm text-gray-600">
                                из
                            </p>
                            <span class="px-2 py-1 text-sm font-semibold text-gray-900 bg-gray-100 rounded-md">
                                {{ pagination.total }}
                            </span>
                            <p class="text-sm text-gray-600">товаров</p>
                        </div>

                        <nav class="flex items-center space-x-1 flex-wrap justify-end" aria-label="Pagination">
                            <button
                                type="button"
                                @click="goToPrevPage"
                                :disabled="pagination.current_page === 1 || loading"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <span class="sr-only">Предыдущая</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
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
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-md scale-105'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300',
                                        'inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 rounded-lg border text-sm font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed'
                                    ]"
                                >
                                    <span v-html="link.label"></span>
                                </button>
                                <span
                                    v-else-if="link.label"
                                    class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 text-sm font-medium text-gray-500"
                                >
                                    <span v-html="link.label"></span>
                                </span>
                            </template>

                            <button
                                type="button"
                                @click="goToNextPage"
                                :disabled="pagination.current_page === pagination.last_page || loading"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <span class="sr-only">Следующая</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
