<script setup lang="ts">
import { onMounted, ref, reactive, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { api } from '@/lib/api';
import type { Product, Category } from '@/types/product';
import type { PaginationMeta, PaginationLink, PaginationData } from '@/types/pagination';

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const deletingIds = ref<Set<number>>(new Set());
const successMessage = ref<string | null>(null);
const editingProduct = ref<Product | null>(null);
const showEditModal = ref(false);
const saving = ref(false);
const formErrors = ref<Record<string, string>>({});
const pagination = ref<PaginationMeta | null>(null);
const paginationLinks = ref<PaginationLink[]>([]);
const currentPage = ref(1);

const form = reactive({
    category_id: '',
    name: '',
    slug: '',
    description: '',
    price: '',
    stock: '',
    is_active: true,
});

const fetchProducts = async (page: number = 1) => {
    loading.value = true;
    error.value = null;
    currentPage.value = page;
    
    try {
        const { data } = await api.get<PaginationData<Product>>('/admin/products', {
            params: { page }
        });
        
        if (data) {
            products.value = data.data || [];
            
            pagination.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1,
                per_page: data.per_page || 10,
                total: data.total || 0,
                from: data.from || 0,
                to: data.to || 0,
            };
            
            paginationLinks.value = data.links || [];
        } else {
            products.value = [];
            pagination.value = null;
            paginationLinks.value = [];
        }
    } catch (err: any) {
        console.error('Ошибка загрузки товаров:', err);
        error.value = err?.response?.data?.message || 'Не удалось загрузить товары';
        products.value = [];
        pagination.value = null;
        paginationLinks.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const { data } = await api.get<Category[]>('/admin/categories');
        categories.value = data || [];
    } catch (err: any) {
        console.error('Ошибка загрузки категорий:', err);
    }
};

const getCategoryName = (categoryId: number | null) => {
    if (!categoryId) return '-';
    const category = categories.value.find(c => c.id === categoryId);
    return category ? category.name : 'Неизвестно';
};

const resetForm = () => {
    form.category_id = '';
    form.name = '';
    form.slug = '';
    form.description = '';
    form.price = '';
    form.stock = '';
    form.is_active = true;
    formErrors.value = {};
};

const openCreateModal = () => {
    editingProduct.value = null;
    resetForm();
    showEditModal.value = true;
};

const openEditModal = (product: Product) => {
    editingProduct.value = product;
    form.category_id = product.category_id.toString();
    form.name = product.name;
    form.slug = product.slug;
    form.description = product.description || '';
    form.price = product.price;
    form.stock = product.stock.toString();
    form.is_active = product.is_active;
    formErrors.value = {};
    showEditModal.value = true;
};

const closeModal = () => {
    showEditModal.value = false;
    editingProduct.value = null;
    resetForm();
};

const saveProduct = async () => {
    saving.value = true;
    formErrors.value = {};
    error.value = null;
    successMessage.value = null;

    try {
        const productData = {
            category_id: Number(form.category_id),
            name: form.name,
            slug: form.slug,
            description: form.description || null,
            price: Number(form.price),
            stock: Number(form.stock),
            is_active: form.is_active,
        };

        if (editingProduct.value) {
            const { data } = await api.put<Product>(`/admin/products/${editingProduct.value.id}`, productData);
            const index = products.value.findIndex(p => p.id === editingProduct.value!.id);
            if (index !== -1) {
                products.value[index] = data;
            }
            successMessage.value = 'Товар успешно обновлен!';
        } else {
            const { data } = await api.post<Product>('/admin/products', productData);
            await fetchProducts(currentPage.value);
            successMessage.value = 'Товар успешно добавлен!';
        }
        closeModal();
        setTimeout(() => successMessage.value = null, 3000);
    } catch (err: any) {
        console.error('Ошибка сохранения товара:', err);
        if (err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        } else {
            error.value = err?.response?.data?.message || 
                        err?.response?.data?.error ||
                        err?.message ||
                        (editingProduct.value ? 'Не удалось обновить товар' : 'Не удалось создать товар');
            setTimeout(() => error.value = null, 5000);
        }
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (id: number) => {
    if (!confirm('Вы уверены, что хотите удалить этот товар?')) {
        return;
    }

    deletingIds.value.add(id);
    error.value = null;
    successMessage.value = null;

    try {
        const response = await api.delete(`/admin/products/${id}`);
        if (response.status === 204 || response.status === 200) {
            successMessage.value = 'Товар успешно удален';
            setTimeout(() => successMessage.value = null, 3000);
            await fetchProducts(currentPage.value);
        }
    } catch (err: any) {
        console.error('Ошибка удаления товара:', err);
        error.value = err?.response?.data?.message || 
                    err?.response?.data?.error ||
                    err?.message ||
                    'Не удалось удалить товар';
        setTimeout(() => error.value = null, 5000);
    } finally {
        deletingIds.value.delete(id);
    }
};

const goToPage = (page: number) => {
    if (page >= 1 && page <= (pagination.value?.last_page || 1)) {
        fetchProducts(page);
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
        goToPage(parseInt(page, 10));
    }
};

const modalTitle = computed(() => editingProduct.value ? 'Редактировать товар' : 'Добавить новый товар');

onMounted(() => {
    fetchProducts();
    fetchCategories();
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Управление товарами</h1>
                    <p v-if="pagination" class="text-sm text-gray-600 mt-1">
                        Всего товаров: <span class="font-medium">{{ pagination.total }}</span>
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Добавить товар
                </button>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div v-if="loading" class="p-8 text-center text-gray-500">
                    Загрузка товаров...
                </div>

                <div v-else-if="products.length === 0" class="p-8 text-center text-gray-500">
                    Товары не найдены
                </div>

                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Название
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Категория
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Цена
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Остаток
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Статус
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="product in products" :key="product.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ product.id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ product.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ getCategoryName(product.category_id) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ Number(product.price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' }) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ product.stock }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    :class="[
                                        product.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800',
                                        'px-2 py-1 text-xs leading-5 font-semibold rounded-full'
                                    ]"
                                >
                                    {{ product.is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    type="button"
                                    @click="openEditModal(product)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                                >
                                    Редактировать
                                </button>
                                <button
                                    type="button"
                                    @click="deleteProduct(product.id)"
                                    :disabled="deletingIds.has(product.id)"
                                    class="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="deletingIds.has(product.id)">Удаление...</span>
                                    <span v-else>Удалить</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Пагинация -->
                <div
                    v-if="pagination && pagination.last_page > 1"
                    class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
                >
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
                                —
                                <span class="font-medium">{{ pagination.to }}</span>
                                из
                                <span class="font-medium">{{ pagination.total }}</span>
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
                                        v-else
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
        </div>

        <!-- Модальное окно для добавления/редактирования товара -->
        <div v-if="showEditModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto">
                    <form @submit.prevent="saveProduct">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ modalTitle }}
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700">Категория</label>
                                    <select
                                        name="category_id"
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Выберите категорию</option>
                                        <option
                                            v-for="category in categories"
                                            :key="category.id"
                                            :value="category.id"
                                        >
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <p v-if="formErrors.category_id" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.category_id) ? formErrors.category_id[0] : formErrors.category_id }}</p>
                                </div>
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Название</label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        v-model="form.name"
                                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    >
                                    <p v-if="formErrors.name" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.name) ? formErrors.name[0] : formErrors.name }}</p>
                                </div>
                                <div>
                                    <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input
                                        type="text"
                                        name="slug"
                                        id="slug"
                                        v-model="form.slug"
                                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    >
                                    <p v-if="formErrors.slug" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.slug) ? formErrors.slug[0] : formErrors.slug }}</p>
                                </div>
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Описание</label>
                                    <textarea
                                        name="description"
                                        id="description"
                                        v-model="form.description"
                                        rows="3"
                                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                    ></textarea>
                                    <p v-if="formErrors.description" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.description) ? formErrors.description[0] : formErrors.description }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700">Цена</label>
                                        <input
                                            type="number"
                                            name="price"
                                            id="price"
                                            v-model="form.price"
                                            step="0.01"
                                            min="0"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        >
                                        <p v-if="formErrors.price" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.price) ? formErrors.price[0] : formErrors.price }}</p>
                                    </div>
                                    <div>
                                        <label for="stock" class="block text-sm font-medium text-gray-700">Остаток</label>
                                        <input
                                            type="number"
                                            name="stock"
                                            id="stock"
                                            v-model="form.stock"
                                            min="0"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        >
                                        <p v-if="formErrors.stock" class="mt-2 text-sm text-red-600">{{ Array.isArray(formErrors.stock) ? formErrors.stock[0] : formErrors.stock }}</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Активен</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="saving"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="saving">Сохранение...</span>
                                <span v-else>Сохранить</span>
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                :disabled="saving"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Отмена
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
