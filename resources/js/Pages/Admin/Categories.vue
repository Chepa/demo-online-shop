<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { api } from '@/lib/api';
import type { Category } from '@/types/product';

const categories = ref<Category[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const deletingIds = ref<Set<number>>(new Set());
const successMessage = ref<string | null>(null);
const editingCategory = ref<Category | null>(null);
const showModal = ref(false);
const saving = ref(false);
const formErrors = ref<Record<string, string>>({});

const form = reactive({
    id: null as number | null,
    name: '',
    slug: '',
    parent_id: null as number | null,
});

const fetchCategories = async () => {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await api.get<Category[]>('/admin/categories');
        categories.value = data || [];
    } catch (err: any) {
        console.error('Ошибка загрузки категорий:', err);
        error.value = err?.response?.data?.message || 'Не удалось загрузить категории';
        categories.value = [];
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingCategory.value = null;
    form.name = '';
    form.slug = '';
    form.parent_id = null;
    formErrors.value = {};
    showModal.value = true;
};

const openEditModal = (category: Category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.slug = category.slug || '';
    form.parent_id = category.parent_id || null;
    formErrors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCategory.value = null;
    formErrors.value = {};
};

const saveCategory = async () => {
    saving.value = true;
    error.value = null;
    successMessage.value = null;
    formErrors.value = {};

    try {
        if (editingCategory.value) {
            // Обновление существующей категории
            const { data } = await api.put<Category>(`/admin/categories/${editingCategory.value.id}`, {
                id: editingCategory.value.id,
                name: form.name,
                slug: form.slug,
                parent_id: form.parent_id,
            });

            // Обновляем категорию в списке
            const index = categories.value.findIndex(c => c.id === editingCategory.value!.id);
            if (index !== -1) {
                categories.value[index] = data;
            }

            successMessage.value = 'Категория успешно обновлена';
        } else {
            // Создание новой категории
            const { data } = await api.post<Category>('/admin/categories', {
                name: form.name,
                slug: form.slug,
                parent_id: form.parent_id,
            });

            categories.value.push(data);
            successMessage.value = 'Категория успешно создана';
        }

        setTimeout(() => {
            successMessage.value = null;
        }, 3000);

        closeModal();
    } catch (err: any) {
        console.error('Ошибка сохранения категории:', err);

        if (err?.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        } else {
            error.value = err?.response?.data?.message || 'Не удалось сохранить категорию';
            setTimeout(() => {
                error.value = null;
            }, 5000);
        }
    } finally {
        saving.value = false;
    }
};

const deleteCategory = async (categoryId: number) => {
    if (!confirm('Вы уверены, что хотите удалить эту категорию?')) {
        return;
    }

    deletingIds.value.add(categoryId);
    error.value = null;
    successMessage.value = null;

    try {
        await api.delete(`/admin/categories/${categoryId}`);

        // Удаляем категорию из списка
        categories.value = categories.value.filter(c => c.id !== categoryId);

        successMessage.value = 'Категория успешно удалена';
        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    } catch (err: any) {
        console.error('Ошибка удаления категории:', err);
        const errorMsg = err?.response?.data?.message || 'Не удалось удалить категорию';
        error.value = errorMsg;
        setTimeout(() => {
            error.value = null;
        }, 5000);
    } finally {
        deletingIds.value.delete(categoryId);
    }
};

const getCategoryName = (categoryId: number | null | undefined): string => {
    if (!categoryId) return '-';
    const category = categories.value.find(c => c.id === categoryId);
    return category?.name || '-';
};

onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Управление категориями</h1>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Добавить категорию
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
                    Загрузка категорий...
                </div>

                <div v-else-if="categories.length === 0" class="p-8 text-center text-gray-500">
                    Категории не найдены
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Название
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Slug
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Родительская категория
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Товаров
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ category.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ category.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ category.slug }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ getCategoryName(category.parent_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ category.products_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        type="button"
                                        @click="openEditModal(category)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteCategory(category.id)"
                                        :disabled="deletingIds.has(category.id)"
                                        class="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ deletingIds.has(category.id) ? 'Удаление...' : 'Удалить' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Модальное окно для создания/редактирования -->
            <div
                v-if="showModal"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
                @click.self="closeModal"
            >
                <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                            {{ editingCategory ? 'Редактировать категорию' : 'Добавить категорию' }}
                        </h3>

                        <form @submit.prevent="saveCategory" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Название *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300': formErrors.name }"
                                />
                                <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">
                                    {{ formErrors.name[0] }}
                                </p>
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700">
                                    Slug *
                                </label>
                                <input
                                    id="slug"
                                    v-model="form.slug"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-300': formErrors.slug }"
                                />
                                <p v-if="formErrors.slug" class="mt-1 text-sm text-red-600">
                                    {{ formErrors.slug[0] }}
                                </p>
                            </div>

                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700">
                                    Родительская категория
                                </label>
                                <select
                                    id="parent_id"
                                    v-model="form.parent_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option :value="null">Нет (корневая категория)</option>
                                    <option
                                        v-for="cat in categories.filter(c => c.id !== editingCategory?.id)"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <p v-if="formErrors.parent_id" class="mt-1 text-sm text-red-600">
                                    {{ formErrors.parent_id[0] }}
                                </p>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Отмена
                                </button>
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ saving ? 'Сохранение...' : (editingCategory ? 'Сохранить' : 'Создать') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
